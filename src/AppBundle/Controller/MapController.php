<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Aspect;
use AppBundle\Entity\Place;
use AppBundle\Form\MapFilterForm;
use AppBundle\Helper;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Everything related to map view
 */
class MapController extends Controller
{
    /**
     * @Route("/map/", name="map")
     */
    public function indexAction()
    {
        $form = $this->createForm(MapFilterForm::class);
        return $this->render(
            'map/map.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    private function applyTypeFilter(QueryBuilder $qb, Request $request)
    {
        $qb->andWhere('a.type <> \'biographicalData\''); // @TODO this is a data fix really.

        $typeExpr = [];
        $types = $request->get('types', \AppBundle\Query::types());

        if ($types & \AppBundle\Query::TYPE_BIOGRAPHICAL) {
            $typeExpr[] = 'a.type = \'beginningOfLife\'';
            $typeExpr[] = 'a.type = \'entryInTheOrder\'';
            $typeExpr[] = 'a.type = \'resignationFromTheOrder\'';
            $typeExpr[] = 'a.type = \'expulsionFromTheOrder\'';
            $typeExpr[] = 'a.type = \'endOfLife\'';
        }

        if ($types & \AppBundle\Query::TYPE_CAREER) {
            $typeExpr[] = 'a.type = \'career\'';
        }

        if ($types & \AppBundle\Query::TYPE_EDUCATION) {
            $typeExpr[] = 'a.type = \'education\'';
        }

        if ($types & \AppBundle\Query::TYPE_OTHER) {
            $typeExpr[] = 'a.type = \'miscellaneous\'';
        }

        $qb->andWhere($typeExpr ? implode(" OR ", $typeExpr) : '0=1');

    }

    private function applyDateFilter(QueryBuilder $qb, Request $request)
    {
        $qb->setParameter('from', (int)$request->get('from', 1490));
        $qb->setParameter('to', (int)$request->get('to', 1890));

        $eb = $qb->expr();

        $range = $eb->andX(
            $eb->gte('COALESCE(a.dateExact, a.dateFrom, 9999)', ':from'),
            $eb->lte('COALESCE(a.dateExact, a.dateTo, 0)', ':to')
        );

        if ($request->get('includeUndated')) {
            $dateWhere = $eb->orX(
                $range,
                $eb->andX(
                    $eb->isNull('a.dateExact'),
                    $eb->isNull('a.dateFrom'),
                    $eb->isNull('a.dateTo')
                )
            );
        } else {
            $dateWhere = $range;
        }

        $qb->andWhere($dateWhere);
    }

    /**
     * @Route("/map/markers", name="map_markers")
     * @param Request $request
     * @return JsonResponse
     */
    public function markerAction(Request $request)
    {
        /** @var QueryBuilder $qb */
        $qb = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Place')
            ->createQueryBuilder('p')
            ->select('p.latitude, p.longitude, p.id, p.placeName, p.continent, p.country, CONCAT(p.latitude, \',\', p.longitude) as grp, count(a.id) as aspect_count')
            ->innerJoin('p.associatedAspects', 'a')
            ->groupBy('grp')
        ;

        $this->applyDateFilter($qb, $request);
        $this->applyTypeFilter($qb, $request);

        $data = $qb
            ->getQuery()
            ->execute();

        $that = $this;
        $data = array_map(function ($p) use ($that) {
            return [
                'lat' => $p['latitude'],
                'lng' => $p['longitude'],
                'url' => $that->generateUrl('map_place', ['id' => $p['id']]),
                'title' => $p['placeName'] . ' (' . Helper::formatCountry($p['country']) . ', ' . Helper::formatContinent($p['continent']) . ')',
                'weight' => (int)$p['aspect_count']
            ];
        }, $data);

        return new JsonResponse($data);
    }

    /**
     * @Route("/map/place/{id}", name="map_place")
     *
     * @param Place $place
     * @param Request $request
     * @return Response
     */
    public function placeAction(Place $place, Request $request)
    {
        /** @var QueryBuilder $qb */
        $qb = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Aspect')
            ->createQueryBuilder('a')
            ->select('a, p, subj, COALESCE(a.dateExact, a.dateFrom, a.dateTo, 99999) as orderDate, (SELECT COUNT(r.id) FROM AppBundle:Relation r WHERE r.aspect = a.id) as relationCount')
            ->addSelect("FIELD(a.type, 'beginningOfLife', 'entryInTheOrder', 'resignationFromTheOrder', 'expulsionFromTheOrder', 'endOfLife', 'education', 'career', 'miscellaneous') as HIDDEN typeField")
            ->addOrderBy('typeField', 'ASC')
            ->addOrderBy('orderDate', 'ASC')
            ->innerJoin('a.places', 'p')
            ->leftJoin('a.subjects', 'subj')
            ->where('p.latitude = :plat AND p.longitude = :plng')
            ->setParameter('plat', $place->getLatitude())
            ->setParameter('plng', $place->getLongitude())
        ;

        $this->applyDateFilter($qb, $request);
        $this->applyTypeFilter($qb, $request);

        $aspectsResult = $qb->getQuery()->execute();

        $aspects = [];
        foreach ($aspectsResult as $aspectRow) {
            /** @var Aspect $aspect */
            $aspect = $aspectRow[0];

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

        return $this->render('map/place.html.twig', [
            'place' => $place,
            'aspects' => $aspects
        ]
        );
    }
}
