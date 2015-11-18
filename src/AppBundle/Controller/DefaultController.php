<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Aspect;
use AppBundle\Entity\Person;
use AppBundle\Helper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig', array());
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
            ->select('p, sbj, src, nam, relout, relin, relouta, relina, relins, reloutt')
            ->leftJoin('p.subjects', 'sbj')
            ->leftJoin('p.sources', 'src')
            ->leftJoin('p.alternateNames', 'nam')
            ->leftJoin('p.relationsOutgoing', 'relout')
            ->leftJoin('p.relationsIncoming', 'relin')
            ->leftJoin('relout.aspect', 'relouta')
            ->leftJoin('relout.target', 'reloutt')
            ->leftJoin('relin.aspect', 'relina')
            ->leftJoin('relin.source', 'relins')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $person) {
            throw new NotFoundHttpException('Person not found');
        }

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

        $aspects = array();
        foreach ($aspectsResult as $aspectRow) {
            /** @var Aspect $aspect */
            $aspect = $aspectRow[0];

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

        return $this->render('default/detail.html.twig', array(
            'person' => $person,
            'aspects' => $aspects
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
     * @Route("/jesuits/", name="list")
     */
    public function listAction()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT COUNT(p.id) FROM AppBundle:Person p WHERE p.isJesuit = 0');
        $nonJesuitCount = $query->getSingleScalarResult();

        $jesuits = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->createQueryBuilder('p')
            ->select('p, s, COALESCE(p.lastName, p.firstName) as sortOrder')
            ->leftJoin('p.subjects', 's')
            ->where('p.isJesuit = 1')
            ->addOrderBy('sortOrder', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->execute()
        ;

        return $this->render('default/list.html.twig', array(
            'jesuitview' => true,
            'personCount' => count($jesuits),
            'otherCount' => $nonJesuitCount,
            'letters' => $this->makeLetterList($jesuits),
        ));
    }

    /**
     * @Route("/non-jesuits/", name="list_nonjesuits")
     */
    public function listNonJesuitsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT COUNT(p.id) FROM AppBundle:Person p WHERE p.isJesuit = 1');
        $jesuitCount = $query->getSingleScalarResult();

        $nonJesuits = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->createQueryBuilder('p')
            ->select('p, s, COALESCE(p.lastName, p.firstName) as sortOrder')
            ->leftJoin('p.subjects', 's')
            ->where('p.isJesuit = 0')
            ->addOrderBy('sortOrder', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->execute()
        ;

        return $this->render('default/list.html.twig', array(
            'jesuitview' => false,
            'personCount' => count($nonJesuits),
            'otherCount' => $jesuitCount,
            'letters' => $this->makeLetterList($nonJesuits),
        ));
    }

    /**
     * @Route("/places/", name="places")
     */
    public function listPlacesAction()
    {
        $places = $this->getDoctrine()->getManager()
            ->createQuery('SELECT p FROM AppBundle:Place p ORDER BY p.placeName asc')
            ->execute()
        ;

        $letters = array();

        foreach ($places as $place) {
            //$person = $person[0]; // 0 = entity, 1 = coalesce(...)
            $letter = strtoupper(substr(Helper::removeAccents($place->getPlaceName()), 0, 1));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = $place;
        }

        return $this->render(':default:places.html.twig', array(
            'placeCount' => count($places),
            'letters' => $letters,
            'grouping' => false
        ));
    }

    /**
     * @Route("/occupations/", name="occupations")
     */
    public function listOccupationsAction()
    {
        $occupations = $this->getDoctrine()->getManager()
            ->createQuery('SELECT a.occupation, a.occupationSlug FROM AppBundle:Aspect a WHERE a.occupation IS NOT NULL GROUP BY a.occupation ORDER BY a.occupation asc')
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        $letters = array();

        foreach ($occupations as $occupation) {
            $letter = strtoupper(substr($occupation['occupation'], 0, 1));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = $occupation;
        }

        return $this->render(':default:occupations.html.twig', array(
            'count' => count($occupations),
            'letters' => $letters,
        ));
    }

    /**
     * @Route("/occupation/{slug}/", name="occupation")
     */
    public function occupationAction($slug)
    {
        $aspects = $this->getDoctrine()->getManager()
            ->createQuery('SELECT a, p, s FROM AppBundle:Aspect a INNER JOIN a.person p LEFT JOIN a.subjects s WHERE a.occupationSlug = :slug')
            ->setParameter('slug', $slug)
            ->execute()
        ;

        $persons = array();

        foreach ($aspects as $aspect) {
            $personId = $aspect->getPerson()->getId();
            if (!array_key_exists($personId, $persons)) {
                $persons[$personId] = array(
                    'person' => $aspect->getPerson(),
                    'aspects' => array()
                );
            }

            $persons[$personId]['aspects'][] = $aspect;
        }

        return $this->render(':default:occupation.html.twig', array(
            'personCount' => count($persons),
            'aspectCount' => count($aspects),
            'occupation' => $aspects[0]->getOccupation(),
            'persons' => $persons,
        ));
    }

    /**
     * @Route("/places/by-country/", name="places_grouped")
     */
    public function listPlacesGroupedAction()
    {
        $em = $this->getDoctrine()->getManager();

        $places = $em
            ->createQuery("SELECT p FROM AppBundle:Place p ORDER BY p.continent asc, p.country asc, p.placeName asc")
            ->execute()
        ;

        $continents = array();

        foreach ($places as $place) {
            $continent = $place->getContinent();
            $country = $place->getCountry();

            if (!array_key_exists($continent, $continents)) {
                $continents[$continent] = array();
            }

            if (!array_key_exists($country, $continents[$continent])) {
                $continents[$continent][$country] = array();
            }

            $continents[$continent][$country][] = $place;
        }

        return $this->render(':default:places.html.twig', array(
            'placeCount' => count($places),
            'continents' => $continents,
            'grouping' => true
        ));
    }

    /**
     * @param Person[] $persons
     * @return array
     */
    private function makeLetterList($persons)
    {
        $letters = array();

        foreach ($persons as $person) {
            $person = $person[0]; // 0 = entity, 1 = coalesce(...)
            $letter = strtoupper(Helper::removeAccents(mb_substr($person->getLastName() ? $person->getLastName() : $person->getFirstName(), 0, 1)));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = $person;
        }

        return $letters;
    }

    /**
     * @Route(path="/subjects/", name="subjects")
     */
    public function subjectsAction()
    {
        $subjects = $this
            ->getDoctrine()
            ->getManager()
            ->createQuery(
                'SELECT s,
                (SELECT COUNT(DISTINCT p.id) FROM AppBundle:Person p INNER JOIN p.subjects ps WHERE ps.id=s.id) as personCount, (SELECT COUNT(DISTINCT a.id) FROM AppBundle:Aspect a INNER JOIN a.subjects asps WHERE asps.id=s.id) as aspectCount FROM AppBundle:Subject s ORDER BY s.title ASC'
            )
            ->execute()
        ;

        $letters = array();

        foreach ($subjects as $subject) {
            $letter = substr($subject[0]->getTitle(), 0, 1);

            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = array(
                'subject' => $subject[0],
                'personCount' => $subject['personCount'],
                'aspectCount' => $subject['aspectCount']
            );
        }

        return $this->render('default/subjects.html.twig', array(
            'showLetterList' => true,
            'fullCount' => count($subjects),
            'letters' => $letters
        ));
    }

    /**
     * @Route(path="/subjects/{scheme}/", name="subjects_grouped")
     */
    public function subjectsGroupedAction($scheme)
    {
        if (!in_array($scheme, array('modern', 'harris'))) {
            throw new NotFoundHttpException('Unknown scheme');
        }

        $em = $this->getDoctrine()->getManager();
        $subjectGroups = $em
            ->createQuery(
                'SELECT g, s FROM AppBundle:SubjectGroup g INNER JOIN g.subjects s WHERE g.scheme=:scheme ORDER BY g.title asc, s.title ASC'
            )
            ->setParameter('scheme', $scheme)
            ->execute()
        ;

        $counts = $em
            ->createQuery(
                'SELECT s.id, (SELECT COUNT(DISTINCT p.id) FROM AppBundle:Person p INNER JOIN p.subjects ps WHERE ps.id=s.id) as personCount, (SELECT COUNT(DISTINCT a.id) FROM AppBundle:Aspect a INNER JOIN a.subjects asps WHERE asps.id=s.id) as aspectCount FROM AppBundle:Subject s INDEX BY s.id ORDER BY s.id'
            )
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        $letters = array();

        $uniqueSubjects = array();
        foreach ($subjectGroups as $group) {
            $letters[$group->getTitle()] = array();
            $groupSubjects = $group->getSubjects();
            foreach ($groupSubjects as $subject) {
                $uniqueSubjects[$subject->getId()] = true;
                $letters[$group->getTitle()][] = array(
                    'subject' => $subject,
                    'personCount' => $counts[$subject->getId()]['personCount'],
                    'aspectCount' => $counts[$subject->getId()]['aspectCount']
                );
            }
        }

        return $this->render('default/subjects.html.twig', array(
            'showLetterList' => false,
            'groupCount' => count($subjectGroups),
            'fullCount' => count($uniqueSubjects),
            'letters' => $letters,
            'scheme' => $scheme
        ));
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
     * @Route("/imprint/", name="imprint")
     */
    public function imprintAction()
    {
        return $this->render('default/imprint.html.twig');
    }

    /**
     * @Route("/workshop/2015/", name="workshop")
     */
    public function workshopAction()
    {
        return $this->render('default/workshop.html.twig', array());
    }

    /**
     * @Route("/debug-sources/", name="debug_sources")
     */
    public function debugSourcesAction()
    {
        return $this->render('default/sources.html.twig', array(
            'sources' => $this->getDoctrine()->getRepository('AppBundle:Source')->findAll()
        ));
    }

}
