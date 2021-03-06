<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Place;
use AppBundle\Entity\SourceGroup;
use AppBundle\Entity\SubjectGroup;
use Doctrine\ORM\Query;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrowseController extends Controller
{
    /**
     * @Route("/sources/", name="sources")
     */
    public function sourcesAction()
    {
        $groups = $this
            ->getDoctrine()
            ->getRepository(SourceGroup::class)
            ->createQueryBuilder('g')
            ->leftJoin('g.sources', 's')
            ->where('g.slug <> ?0 and g.slug <> ?1')
            ->getQuery()
            ->execute(['wp', 'viaf'])
        ;
        $sources = $this
            ->getDoctrine()
            ->getManager()
            ->createQuery('SELECT s FROM AppBundle:Source s WHERE s.sourceGroup IS NULL order by s.id asc')
            ->getResult()
        ;

        return $this->render('default/sources.html.twig', [
            'sources' => $sources,
            'groups' => $groups,
        ]
        );
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
            ->addOrderBy('p.nameForSorting', 'ASC')
            ->getQuery()
            ->getResult('LetterList')
        ;

        $count = 0;
        foreach ($jesuits as $letter) {
            $count += count($letter);
        }

        return $this->render('default/list.html.twig', [
            'jesuitview' => true,
            'personCount' => $count,
            'otherCount' => $nonJesuitCount,
            'letters' => $jesuits,
        ]);
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
            ->addOrderBy('p.nameForSorting', 'ASC')
            ->getQuery()
            ->getResult('LetterList')
        ;

        $count = 0;
        foreach ($nonJesuits as $letter) {
            $count += count($letter);
        }

        return $this->render('default/list.html.twig', [
            'jesuitview' => false,
            'personCount' => $count,
            'otherCount' => $jesuitCount,
            'letters' => $nonJesuits,
        ]);
    }

    /**
     * @Route("/places/", name="places")
     */
    public function listPlacesAction()
    {
        /** @var Place[] $places */
        $letters = $this->getDoctrine()->getManager()
            ->createQuery('SELECT p FROM AppBundle:Place p ORDER BY p.placeName asc')
            ->getResult('LetterList')
        ;

        $count = array_reduce(array_map(function (array $letter) {
            return count($letter);
        }, $letters), function ($item, $carry) {
            return $item + $carry;
        }, 0);

        return $this->render(':default:places.html.twig', [
            'placeCount' => $count,
            'letters' => $letters,
            'grouping' => false
        ]
        );
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

        $letters = [];

        foreach ($occupations as $occupation) {
            $letter = strtoupper(substr($occupation['occupation'], 0, 1));
            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = [];
            }

            $letters[$letter][] = $occupation;
        }

        return $this->render(':default:occupations.html.twig', [
            'count' => count($occupations),
            'letters' => $letters,
        ]
        );
    }

    /**
     * @Route("/places/by-country/", name="places_grouped")
     */
    public function listPlacesGroupedAction()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Place[] $places */
        $places = $em
            ->createQuery("SELECT p FROM AppBundle:Place p ORDER BY p.continent asc, p.country asc, p.placeName asc")
            ->execute()
        ;

        $continents = [];

        foreach ($places as $place) {
            $continent = $place->getContinent();
            $country = $place->getCountry();

            if (!array_key_exists($continent, $continents)) {
                $continents[$continent] = [];
            }

            if (!array_key_exists($country, $continents[$continent])) {
                $continents[$continent][$country] = [];
            }

            $continents[$continent][$country][] = $place;
        }

        return $this->render(':default:places.html.twig', [
            'placeCount' => count($places),
            'continents' => $continents,
            'grouping' => true
        ]
        );
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

        $letters = [];

        foreach ($subjects as $subject) {
            $letter = substr($subject[0]->getTitle(), 0, 1);

            if (!array_key_exists($letter, $letters)) {
                $letters[$letter] = [];
            }

            $letters[$letter][] = [
                'subject' => $subject[0],
                'personCount' => $subject['personCount'],
                'aspectCount' => $subject['aspectCount']
            ];
        }

        return $this->render('default/subjects.html.twig', [
            'showLetterList' => true,
            'fullCount' => count($subjects),
            'letters' => $letters
        ]
        );
    }

    /**
     * @Route(path="/subjects/grouped/", name="subjects_grouped")
     */
    public function subjectsGroupedAction()
    {
        $em = $this->getDoctrine()->getManager();
        /** @var SubjectGroup[] $subjectGroups */
        $subjectGroups = $em
            ->createQuery(
                'SELECT g, s FROM AppBundle:SubjectGroup g INNER JOIN g.subjects s ORDER BY g.title asc, s.title ASC'
            )
            ->execute()
        ;

        $counts = $em
            ->createQuery(
                'SELECT s.id, (SELECT COUNT(DISTINCT p.id) FROM AppBundle:Person p INNER JOIN p.subjects ps WHERE ps.id=s.id) as personCount, (SELECT COUNT(DISTINCT a.id) FROM AppBundle:Aspect a INNER JOIN a.subjects asps WHERE asps.id=s.id) as aspectCount FROM AppBundle:Subject s INDEX BY s.id ORDER BY s.id'
            )
            ->getResult(Query::HYDRATE_ARRAY)
        ;

        $letters = [];

        $uniqueSubjects = [];
        foreach ($subjectGroups as $group) {
            $letters[$group->getTitle()] = [];
            $groupSubjects = $group->getSubjects();
            foreach ($groupSubjects as $subject) {
                $uniqueSubjects[$subject->getId()] = true;
                $letters[$group->getTitle()][] = [
                    'subject' => $subject,
                    'personCount' => $counts[$subject->getId()]['personCount'],
                    'aspectCount' => $counts[$subject->getId()]['aspectCount']
                ];
            }
        }

        return $this->render('default/subjects.html.twig', [
            'showLetterList' => false,
            'groupCount' => count($subjectGroups),
            'fullCount' => count($uniqueSubjects),
            'letters' => $letters,
            'scheme' => 'grouped'
        ]
        );
    }
}
