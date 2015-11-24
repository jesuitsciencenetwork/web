<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Aspect;
use AppBundle\Entity\Person;
use AppBundle\Entity\Subject;
use AppBundle\Entity\SubjectGroup;
use AppBundle\Helper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="splash")
     */
    public function splashAction()
    {
        return $this->render('splash.html.twig');
    }

    /**
     * @Route("/home/", name="homepage")
     */
    public function startAction()
    {
        $stats = $this->getDoctrine()->getManager()->createQuery(
            'SELECT COUNT(p.id) as nb, MAX(p.lastMod) as lm FROM AppBundle:Person p'
        )->getResult(Query::HYDRATE_ARRAY);

        $search = $this->get('jsn.search');
        return $this->render('static/index.html.twig', array(
            'subjectGroupTree' => $search->getSubjectGroupTree(),
            'stats' => $stats[0]
        ));
    }

    /**
     * @Route("/p/{id}/", requirements={"id" = "\d+"}, name="detail")
     */
    public function detailAction($id, Request $request)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->createQueryBuilder('p')
            ->select('p, sbj, relout, relin, relouta, relina')
            ->leftJoin('p.subjects', 'sbj')
            ->leftJoin('p.relationsOutgoing', 'relout')
            ->leftJoin('p.relationsIncoming', 'relin')
            ->leftJoin('relout.aspect', 'relouta')
            ->leftJoin('relin.aspect', 'relina')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $person) {
            throw new NotFoundHttpException('Person not found');
        }

        $relations = $this->getDoctrine()->getConnection()->executeQuery(
            'SELECT IF(:id = r.source_id, t.id, s.id) as id, IF(:id = r.source_id, t.display_name, s.display_name) as name FROM relations r LEFT JOIN person t ON r.target_id = t.id LEFT JOIN person s ON r.source_id = s.id WHERE r.source_id = :id OR r.target_id = :id GROUP BY id ORDER BY IF(:id = r.source_id, t.list_name, s.list_name) ASC',
            array('id' => $person->getId())
        )->fetchAll();

        $aspectsResult = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Aspect')
            ->createQueryBuilder('a')
            ->select('a, p, subj, COALESCE(a.dateExact, a.dateFrom, a.dateTo, 99999) as orderDate, (SELECT COUNT(r.id) FROM AppBundle:Relation r WHERE r.aspect = a.id) as relationCount')
            ->addSelect("FIELD(a.type, 'beginningOfLife', 'entryInTheOrder', 'resignationFromTheOrder', 'expulsionFromTheOrder', 'endOfLife', 'education', 'career', 'miscellaneous') as HIDDEN typeField")
            ->addOrderBy('typeField', 'ASC')
            ->addOrderBy('orderDate', 'ASC')
            ->leftJoin('a.places', 'p')
            ->leftJoin('a.subjects', 'subj')
            ->where('a.person = :person')
            ->setParameter('person', $person->getId())
            ->getQuery()
            ->execute()
        ;

        $twig = $this->get('twig');
        $places = array();
        $aspects = array();
        foreach ($aspectsResult as $aspectRow) {
            /** @var Aspect $aspect */
            $aspect = $aspectRow[0];

            foreach ($aspect->getPlaces() as $place) {
                if (!array_key_exists($place->getId(), $places)) {
                    $places[$place->getId()] = array(
                        'lat' => $place->getLatitude(),
                        'lng' => $place->getLongitude(),
                        'name' => $place->getPlaceName() . ', ' . Helper::formatCountry($place->getCountry()),
                        'types' => array(),
                        'aspects' => array()
                    );
                }
                $places[$place->getId()]['aspects'][] = $twig->render('include/aspect.html.twig', array(
                    'aspect' => $aspect
                ));
                $places[$place->getId()]['types'][] = $aspect->getMarkerLabel();
            }

            if ($aspect->getDateExact() || $aspect->getDateFrom() || $aspect->getDateTo()) {
                $key = 'Dated';
            } else {
                $key = 'Undated';
            }

            $type = ucfirst($aspect->getType());
            if (in_array($type, array('BeginningOfLife', 'EntryInTheOrder', 'ResignationFromTheOrder', 'ExpulsionFromTheOrder', 'EndOfLife'))) {
                $type = 'Biographical data';
            } elseif ('Miscellaneous' === $type) {
                if ($aspectRow['relationCount']) {
                    continue;
                }
            }

            if (!array_key_exists($type, $aspects)) {
                $aspects[$type] = array('Dated' => array(), 'Undated' => array());
            }

            $aspects[$type][$key][] = $aspect;
        }

        foreach ($places as &$place) {
            $place['types'] = array_unique($place['types']);

            if (count($place['types']) == 1) {
                $place['label'] = $place['types'][0];
            } else {
                $place['label'] = null;
            }
            unset($place['types']);
        }

        return $this->render('default/detail.html.twig', array(
            'person' => $person,
            'relations' => $relations,
            'aspects' => $aspects,
            'places' => $places
        ));
    }

    /**
     * @Route("/p/{id}.{format}", requirements={"format" = "json|yml|xml"}, name="data")
     * @param $id
     * @param $format
     * @return Response
     */
    public function serializeAction($id, $format)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->createQueryBuilder('p')
            ->select('p, sbj, src, nam, rel')
            ->leftJoin('p.subjects', 'sbj')
            ->leftJoin('p.sources', 'src')
            ->leftJoin('p.alternateNames', 'nam')
            ->leftJoin('p.relationsOutgoing', 'rel')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $person) {
            throw new NotFoundHttpException('Person not found');
        }

        $jms = $this->get('jms_serializer');
        $response = new Response($jms->serialize($person, $format, SerializationContext::create()->setGroups(array('Default', 'Person'))));

        switch ($format) {
            case 'yml':
                $response->headers->set('Content-Type', 'text/plain');
                break;
            case 'xml':
                $response->headers->set('Content-Type', 'application/xml');
                break;
            case 'json':
                $response->headers->set('Content-Type', 'application/json');
                break;
        }

        return $response;
    }

    /**
     * @Route("/random/", name="random")
     */
    public function randomAction()
    {
        /** @var EntityRepository $repo */
        $repo =  $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person');
        $person = $repo
            ->createQueryBuilder('p')
            ->addSelect('RAND() as HIDDEN rand')
            ->setMaxResults(1)
            ->orderBy('rand')
            ->getQuery()
            ->getSingleResult()
        ;

        return $this->redirect($this->generateUrl('detail', array('id' => $person->getId())));
    }


    /**
     * @Route("/viaf/{id}/", name="viaf")
     */
    public function viafAction($id)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->findOneBy(array('viafId' => $id));

        if (!$person) {
            $this
                ->get('braincrafted_bootstrap.flash')
                ->alert(sprintf(
                    'The VIAF ID "%s" could not be found. Please try searching our database instead.',
                    $id
                ))
            ;
            return $this->redirect($this->generateUrl('search'));
        }

        return $this->redirect(
            $this->generateUrl('detail', array('id' => $person->getId()))
        );
    }
}
