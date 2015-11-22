<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use AppBundle\Entity\Subject;
use AppBundle\Form\AdvancedSearchForm;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
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
            ->select('p, a, s')
            ->leftJoin('p.aspects', 'a')
            ->leftJoin('a.subjects', 's')
            ->add('where', $qb->expr()->in('p.id', '?1'))
            ->setParameter(1, $personIds)
            ->addOrderBy('p.listName', 'ASC')
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
