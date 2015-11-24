<?php

namespace AppBundle\Controller;

use AppBundle\Helper;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;

class JsonController extends Controller
{
    /**
     * @Route("/persons.json", name="autocomplete_persons")
     */
    public function autocompleteAction(Request $request)
    {
        $q = $request->get('q');

        if (!$q) {
            return new JsonResponse(array());
        }

        $data = array();

        $personQuery = $this->getDoctrine()->getConnection()->executeQuery('SELECT p.id, p.display_name as pn, a.display_name as an FROM person p LEFT JOIN alternate_name a ON a.person_id=p.id WHERE p.display_name LIKE :query OR a.display_name LIKE :query', array('query' => "%$q%"));

//        getManager()->createQuery(
//            'SELECT p.id, p.displayName, n.id, n.displayName FROM AppBundle:Person p LEFT JOIN p.alternateNames n WHERE p.displayName LIKE :query OR n.displayName LIKE :query'
//        )
//            ->setParameter('query', '%'.$q.'%')
//            ->setHydrationMode(Query::HYDRATE_SIMPLEOBJECT)
//            ->execute();

        $persons = array();
        while ($row = $personQuery->fetch()) {
            if (!array_key_exists($row['id'], $persons)) {
                $persons[$row['id']] = array(
                    'displayName' => $row['pn'],
                    'alternateNames' => array()
                );
            }
            if ($row['an']) {
                $persons[$row['id']]['alternateNames'][] = $row['an'];
            }
        }

        foreach ($persons as $id => $person) {
            $personData = array(
                'url' => $this->generateUrl('detail', ['id' => $id], UrlGenerator::ABSOLUTE_URL),
                'value' => $person['displayName'],

            );
            if (false === strpos(strtolower($person['displayName']), strtolower($q))) {
                foreach ($person['alternateNames'] as $name) {
                    if (false !== strpos(strtolower($name), strtolower($q))) {
                        $personData['text'] = 'Also known as: ' . $name;
                        break;
                    }
                }
            }
            $data[] = $personData;
        }

        return new JsonResponse($data);
//        return $this->render('blank.html.twig', array('data' => $data));
    }

    /**
     * @Route("/places.json", name="autocomplete_places")
     */
    public function autocompletePlacesAction(Request $request)
    {
        $q = $request->get('q');
        $data = array();

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->contains('placeName', $q));
        $places = $this
            ->getDoctrine()
            ->getManager()
            ->createQuery('SELECT p.placeName FROM AppBundle:Place p WHERE p.placeName LIKE :query')
            ->setParameter('query', "%$q%")
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult()
        ;

        foreach ($places as $place) {
            $data[] = array(
                'url' => $this->generateUrl('search', ['place'=>$place['placeName']], UrlGenerator::ABSOLUTE_URL),
                'value' => $place['placeName'],
            );
        }

        return new JsonResponse($data);
    }

    /**
     * @Route(path="/subjects.json", name="subjects_json")
     */
    public function subjectsJsonAction()
    {
        $data = array();

        $subjects = $this->getDoctrine()->getManager()->createQuery(
            'SELECT s FROM AppBundle:Subject s ORDER BY s.title ASC'
        )->execute();

        foreach ($subjects as $subject) {
            $data[] = array(
                'url' => $this->generateUrl('search', ['subjects' => $subject->getId()], UrlGenerator::ABSOLUTE_URL),
                'value' => $subject->getTitle()
            );
        }
        return new JsonResponse($data);
    }

    /**
     * @Route(path="/occupations.json", name="occupations_json")
     */
    public function occupationsAction()
    {
        $data = array();

        $occupations = $this->getDoctrine()->getManager()->createQuery(
            'SELECT a.occupationSlug, a.occupation FROM AppBundle:Aspect a WHERE a.occupation IS NOT NULL GROUP BY a.occupation ORDER BY a.occupation ASC'
        )
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();

        foreach ($occupations as $occupation) {
            $data[] = array(
                'url' => $this->generateUrl('search', ['occupation' => $occupation['occupationSlug']], UrlGenerator::ABSOLUTE_URL),
                'value' => ucfirst($occupation['occupation'])
            );
        }
        return new JsonResponse($data);
    }

    /**
     * @Route(path="/regions.json", name="regions_json")
     */
    public function regionsAction()
    {
        $data = array();

        $continents = $this->getDoctrine()->getManager()->createQuery(
            'SELECT p.continent FROM AppBundle:Place p WHERE p.continent IS NOT NULL GROUP BY p.continent ORDER BY p.continent ASC'
        )
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();

        $countries = $this->getDoctrine()->getManager()->createQuery(
            'SELECT p.country FROM AppBundle:Place p WHERE p.country IS NOT NULL GROUP BY p.country ORDER BY p.country ASC'
        )
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->getResult();


        foreach ($continents as $continent) {
            $data[] = array(
                'url' => $this->generateUrl('search', ['continent' => $continent['continent']], UrlGenerator::ABSOLUTE_URL),
                'value' => Helper::formatContinent($continent['continent']),
                'text' => 'Continent'
            );
        }

        foreach ($countries as $country) {
            $data[] = array(
                'url' => $this->generateUrl('search', ['country' => $country['country']], UrlGenerator::ABSOLUTE_URL),
                'value' => Helper::formatCountry($country['country']),
                'text' => 'Country'
            );
        }

        return new JsonResponse($data);
    }
}
