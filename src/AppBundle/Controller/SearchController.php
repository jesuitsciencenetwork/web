<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use AppBundle\Entity\Subject;
use AppBundle\Form\AdvancedSearchForm;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;

class SearchController extends Controller
{
    /**
     * @Route("/search/", name="search")
     */
    public function searchAction(Request $request)
    {
        $form = $this->createForm(new AdvancedSearchForm());

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->render('default/results.html.twig', array(
                'form' => $form->createView()
            ));
        }

        return $this->render('default/search.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/persons.json", name="autocomplete_persons")
     */
    public function autocompleteAction(Request $request)
    {
        $q = $request->get('q');
        $data = array();

        $persons = $this->getDoctrine()->getManager()->createQuery(
            'SELECT p, n FROM AppBundle:Person p LEFT JOIN p.alternateNames n WHERE p.firstName LIKE :query OR p.lastName LIKE :query OR n.displayName LIKE :query'
        )->setParameter('query', '%'.$q.'%')->execute();

        foreach ($persons as $person) {
            $personData = array(
                'url' => $this->generateUrl('detail', ['id' => $person->getId()], UrlGenerator::ABSOLUTE_URL),
                'value' => $person->getDisplayName(),

            );
            if (false === strpos(strtolower($person->getDisplayName()), strtolower($q))) {
                foreach ($person->getAlternateNames() as $name) {
                    if (false !== strpos(strtolower($name->getDisplayName()), strtolower($q))) {
                        $personData['text'] = 'Also known as: ' . $name->getDisplayName();
                        break;
                    }
                }
            }
            $data[] = $personData;
        }

        $data[] = array(
            'url' => $this->generateUrl('list'),
            'value' => '<em>Didn\'t find what you were looking for?</em>'
        );

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

        $places = $this->getDoctrine()->getManager()->createQuery(
            'SELECT DISTINCT a.placeName FROM AppBundle:Aspect a WHERE a.placeName LIKE :query'
        )->setParameter('query', '%'.$q.'%')->execute();

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
                'url' => $this->generateUrl('subject', ['slug' => $subject->getSlug()], UrlGenerator::ABSOLUTE_URL),
                'value' => $subject->getTitle()
            );
        }
        return new JsonResponse($data);
    }

    /**
     * @Route("/subject/{slug}/", name="subject")
     */
    public function subjectAction(Subject $subject)
    {
        $personIds = $subject->getAssociatedPersons()->map(function (Person $person) {
            return $person->getId();
        });

        /** @var QueryBuilder $qb */
        $qb = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->createQueryBuilder('p');

        /** @var Collection|Person[] $personList */
        $personList = $qb
            ->leftJoin('p.aspects', 'a')
            ->leftJoin('a.subjects', 's')
            ->add('where', $qb->expr()->in('p.id', '?1'))
            ->setParameter(1, $personIds)
            ->addOrderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->execute()
        ;

        $persons = array();

        foreach ($personList as $person) {
            $highlightedAspect = null;
            foreach ($person->getAspects() as $aspect) {
                $subjectFound = false;
                foreach ($aspect->getSubjects() as $aspectSubject) {
                    if ($subject->getId() == $aspectSubject->getId()) {
                        $subjectFound = true;
                        break;
                    }
                }
                if (!$subjectFound) continue;

                $highlightedAspect = $aspect;
                break;
            }

            $persons[] = array(
                'person' => $person,
                'aspect' => $highlightedAspect
            );
        }

        return $this->render('default/subject.html.twig', array(
            'subject' => $subject,
            'persons' => $persons
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
