<?php

namespace AppBundle\Controller;

use AppBundle\DTO\Location;
use AppBundle\DTO\Radius;
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

class BrowseController extends Controller
{
    /**
     * @Route("/sources/", name="sources")
     */
    public function sourcesAction()
    {
        $sources = $this->getDoctrine()->getManager()->createQuery('SELECT s FROM AppBundle:Source s WHERE s.genre <> ?0 and s.genre <> ?1 order by s.id asc')->execute(array('VIAF', 'GND'));

        return $this->render('default/sources.html.twig', array(
            'sources' => $sources
        ));
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
            ->select('p, s')
            ->leftJoin('p.subjects', 's')
            ->where('p.isJesuit = 1')
            ->addOrderBy('p.listName', 'ASC')
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
            ->select('p, s')
            ->leftJoin('p.subjects', 's')
            ->where('p.isJesuit = 0')
            ->addOrderBy('p.listName', 'ASC')
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
            $letter = strtoupper(substr(Helper::removeAccents(
                "'s-Hertogenbosch" === $place->getPlaceName() ? 'Hertogenbosch' : $place->getPlaceName()
            ), 0, 1));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = array();
            }

            $letters[$letter][] = $place;
        }
        ksort($letters);

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
//            $person = $person[0]; // 0 = entity, 1 = coalesce(...)
            $letter = strtoupper(Helper::removeAccents(mb_substr($person->getListName(), 0, 1)));
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

}
