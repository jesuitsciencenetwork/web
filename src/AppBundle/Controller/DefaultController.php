<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Aspect;
use AppBundle\Entity\Person;
use AppBundle\Entity\Relation;
use AppBundle\Helper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    private static $colors = [
        '' => '#bdc3c7',
        'agentOf' => '#2c3e50',
        'brotherOf' => '#95a5a6',
        'colleagueOf' => '#f1c40f',
        'competitorOf' => '#e67e22',
        'fatherOf' => '#1abc9c',
        'inferiorOf' => '#d35400',
        'inspiredBy' => '#e74c3c',
        'predecessorOf' => '#2ecc71',
        'privateTeacherOf' => '#c0392b',
        'professorOf' => '#3498db',
        'pupilOf' => '#9b59b6',
        'reviewerOf' => '#34495e',
        'schoolTeacherOf' => '#8e44ad',
        'sonOf' => '#16a085',
        'studentOf' => '#2980b9',
        'successorOf' => '#27ae60',
        'tutorOf' => '#7f8c8d',
    ];

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
        return $this->render('static/index.html.twig', [
            'subjectGroupTree' => $search->getSubjectGroupTree(),
            'stats' => $stats[0]
        ]
        );
    }

    /**
     * @Route("/p/{id}/", requirements={"id" = "\d+"}, name="detail")
     */
    public function detailAction($id)
    {
        /** @var Person $person */
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
            'SELECT 
                id, display_name AS name
             FROM person 
             WHERE 
                id IN (
                    SELECT r1.source_id FROM relations r1 WHERE r1.target_id = :id
                    UNION 
                    SELECT r2.target_id FROM relations r2 WHERE r2.source_id = :id
                )
             ORDER BY list_name ASC',
            ['id' => $person->getId()]
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
        $places = [];
        $aspects = [];
        foreach ($aspectsResult as $aspectRow) {
            /** @var Aspect $aspect */
            $aspect = $aspectRow[0];

            foreach ($aspect->getPlaces() as $place) {
                if (!array_key_exists($place->getId(), $places)) {
                    $places[$place->getId()] = [
                        'lat' => $place->getLatitude(),
                        'lng' => $place->getLongitude(),
                        'name' => $place->getPlaceName() . ', ' . Helper::formatCountry($place->getCountry()),
                        'types' => [],
                        'aspects' => []
                    ];
                }
                $places[$place->getId()]['aspects'][] = $twig->render('include/aspect.html.twig', [
                    'aspect' => $aspect
                ]
                );
                $places[$place->getId()]['types'][] = $aspect->getMarkerLabel();
            }

            if ($aspect->getDateExact() || $aspect->getDateFrom() || $aspect->getDateTo()) {
                $key = 'Dated';
            } else {
                $key = 'Undated';
            }

            $type = ucfirst($aspect->getType());
            if (in_array($type, ['BeginningOfLife', 'EntryInTheOrder', 'ResignationFromTheOrder', 'ExpulsionFromTheOrder', 'EndOfLife']
            )) {
                $type = 'Biographical data';
            } elseif ('Miscellaneous' === $type) {
                if ($aspectRow['relationCount']) {
                    continue;
                }
            }

            if (!array_key_exists($type, $aspects)) {
                $aspects[$type] = ['Dated' => [], 'Undated' => []];
            }

            $aspects[$type][$key][] = $aspect;
        }

        $nodes = [
            [
                'id' => (string)$person->getId(),
                'group' => $person->isJesuit() ? 'j' : 'n',
                'value' => 3,
                'shape' => 'box',
                'label' => '                 ',
            ]
        ];

        $edges = [];

        foreach ($person->getRelationsIncoming() as $rel) {
            foreach ($rel->getAspect()->getPlaces() as $place) {
                if (!array_key_exists($place->getId(), $places)) {
                    $places[$place->getId()] = [
                        'lat' => $place->getLatitude(),
                        'lng' => $place->getLongitude(),
                        'name' => $place->getPlaceName() . ', ' . Helper::formatCountry($place->getCountry()),
                        'types' => [],
                        'aspects' => []
                    ];
                }
                $places[$place->getId()]['aspects'][] = $twig->render('include/aspect.html.twig', [
                        'aspect' => $rel->getAspect()
                    ]
                );
                $places[$place->getId()]['types'][] = $rel->getAspect()->getMarkerLabel();
            }
            $nodes[(string)$rel->getSource()->getId()] = [
                'id' => (string)$rel->getSource()->getId(),
                'group' => $rel->getSource()->isJesuit() ? 'j' : 'n',
            ];
            $edges[] = [
                'from' => (string)$rel->getSource()->getId(),
                'to' => (string)$person->getId(),
                'arrows' => 'to',
                'color' => self::$colors[$rel->getValue()],
            ];
        }

        foreach ($person->getRelationsOutgoing() as $rel) {
            foreach ($rel->getAspect()->getPlaces() as $place) {
                if (!array_key_exists($place->getId(), $places)) {
                    $places[$place->getId()] = [
                        'lat' => $place->getLatitude(),
                        'lng' => $place->getLongitude(),
                        'name' => $place->getPlaceName() . ', ' . Helper::formatCountry($place->getCountry()),
                        'types' => [],
                        'aspects' => []
                    ];
                }
                $places[$place->getId()]['aspects'][] = $twig->render('include/aspect.html.twig', [
                        'aspect' => $rel->getAspect()
                    ]
                );
                $places[$place->getId()]['types'][] = $rel->getAspect()->getMarkerLabel();
            }
            $nodes[(string)$rel->getTarget()->getId()] = [
                'id' => (string)$rel->getTarget()->getId(),
                'group' => $rel->getTarget()->isJesuit() ? 'j' : 'n',
            ];
            $edges[] = [
                'from' => (string)$person->getId(),
                'to' => (string)$rel->getTarget()->getId(),
                'arrows' => 'to',
                'color' => self::$colors[$rel->getValue()],
            ];
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

        $relationsOutgoing = $this
            ->getDoctrine()
            ->getRepository(Aspect::class)
            ->createQueryBuilder('a')
            ->select('a')
            ->innerJoin('a.relations', 'r')
            ->where('r.source = :person')
            ->groupBy('a.id')
            ->setParameter('person', $person->getId())
            ->getQuery()
            ->execute()
        ;

        return $this->render('default/detail.html.twig', [
            'person' => $person,
            'relations' => $relations,
            'relationsOutgoing' => $relationsOutgoing,
            'aspects' => $aspects,
            'places' => $places,
            'nodes' => array_values($nodes), // use keys only for dedup
            'edges' => $edges,
        ]
        );
    }

    /**
     * @Route("/p/{id}/graph", requirements={"id" = "\d+"}, name="graph")
     */
    public function graphAction(Person $person)
    {
        $nodes = [
            [
                'id' => (string)$person->getId(),
                'group' => $person->isJesuit() ? 'j' : 'n',
                'size' => 20,
//                'fixed' => true,
                'label' => $person->getDisplayName(),
                'shape' => 'box',
                'borderWidthSelected' => 0.5,
                'margin' => 15,
                'font' => ['color' => $person->isJesuit() ? '#fff' : '#444']
            ]
        ];

        $edges = [];

        foreach ($person->getRelationsIncoming() as $rel) {
            $nodes[(string)$rel->getSource()->getId()] = [
                'id' => (string)$rel->getSource()->getId(),
                'url' => $this->generateUrl('graph', ['id' => $rel->getSource()->getId()]),
                'group' => $rel->getSource()->isJesuit() ? 'j' : 'n',
                'label' => $rel->getSource()->getDisplayName(),
            ];
            $edges[] = [
                'from' => (string)$rel->getSource()->getId(),
                'to' => (string)$person->getId(),
                'arrows' => 'to',
                'color' => self::$colors[$rel->getValue()],
                'label' => $rel->getPrettyValue(),
            ];
        }

        foreach ($person->getRelationsOutgoing() as $rel) {
            $nodes[(string)$rel->getTarget()->getId()] = [
                'id' => (string)$rel->getTarget()->getId(),
                'url' => $this->generateUrl('graph', ['id' => $rel->getTarget()->getId()]),
                'group' => $rel->getTarget()->isJesuit() ? 'j' : 'n',
                'label' => $rel->getTarget()->getDisplayName(),
            ];
            $edges[] = [
                'from' => (string)$person->getId(),
                'to' => (string)$rel->getTarget()->getId(),
                'arrows' => 'to',
                'color' => self::$colors[$rel->getValue()],
                'label' => $rel->getPrettyValue(),
            ];
        }

        return $this->render('default/graph.html.twig', [
            'person' => $person,
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ]);
    }

    /**
     * @Route("/lisiak-graph/{format}", name="lisiak")
     */
    public function lisiakAction($format)
    {
        if ($format !== 'graph' && $format !== 'txt') {
            throw new NotFoundHttpException('Format is invalid');
        }

        $conn = $this->getDoctrine()->getConnection();
        $q = $conn->query('select distinct person_id from aspect where type = \'beginningOfLife\' and source_id = 2');
        $masterSet = $q->fetchAll(\PDO::FETCH_COLUMN, 0);

        $qu = $this->getDoctrine()
            ->getRepository(Relation::class)
            ->createQueryBuilder('r')
            ->select('r, s, t')
            ->leftJoin('r.source', 's')
            ->leftJoin('r.target', 't')
            ->where('s.id IN (:ids) OR t.id IN (:ids)')
            ->andWhere('r.value = \'professorOf\' OR r.value = \'studentOf\'')
            ->setParameter('ids', $masterSet)
            ->getQuery()
            ->execute()
        ;

        $nodes = [];
        $edges = [];

        $arrows = [
            'colleagueOf' => 'normal',
            'inferiorOf' => 'invempty',
            'predecessorOf' => 'obox',
            'professorOf' => 'diamond',
            'studentOf' => 'ediamond',
            'successorOf' => 'box',
        ];

        foreach ($qu as $rel) {
            $nodes[(string)$rel->getSource()->getId()] = [
                'id' => (string)$rel->getSource()->getId(),
                'group' => in_array((string)$rel->getSource()->getId(), $masterSet) ? 'j' : 'n',
                'label' => $rel->getSource()->getDisplayName(),
            ];
            $nodes[(string)$rel->getTarget()->getId()] = [
                'id' => (string)$rel->getTarget()->getId(),
                'group' => in_array((string)$rel->getTarget()->getId(), $masterSet) ? 'j' : 'n',
                'label' => $rel->getTarget()->getDisplayName(),
            ];

            $edges[] = [
                'from' => (string)$rel->getSource()->getId(),
                'to' => (string)$rel->getTarget()->getId(),
                'arrows' => 'to',
                'color' => self::$colors[$rel->getValue()],
                'label' => $rel->getPrettyValue(),
                'arr' => $arrows[$rel->getValue()],
            ];
        }

        if ($format === 'txt') {
            $r = new Response();
            $r->headers->set('Content-Type', 'text/plain');
            return $this->render('default/lisiak-gv.txt.twig', [
                'nodes' => array_values($nodes),
                'edges' => $edges,
            ], $r);
        } else {
            return $this->render('default/lisiak-graph.html.twig', [
                'nodes' => array_values($nodes),
                'edges' => $edges,
            ]);
        }

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
        $response = new Response($jms->serialize($person, $format, SerializationContext::create()->setGroups(
            ['Default', 'Person']
        )));

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

        return $this->redirect($this->generateUrl('detail', ['id' => $person->getId()]
        ));
    }


    /**
     * @Route("/viaf/{id}/", name="viaf")
     */
    public function viafAction($id)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->findOneBy(['viafId' => $id]);

        if (!$person) {
            return $this->render('default/search.html.twig', [
                'message' => sprintf(
                    'The VIAF ID "%s" could not be found. Please try searching our database instead.',
                    $id
                )
            ]);
        }

        return $this->redirect(
            $this->generateUrl('detail', ['id' => $person->getId()])
        );
    }
}
