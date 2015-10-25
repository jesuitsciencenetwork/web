<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use AppBundle\Helper;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/p/{id}/", name="detail")
     */
    public function detailAction($id, Request $request)
    {
        $person = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Person')
            ->find($id);

        if (null === $person) {
            throw new NotFoundHttpException('Person not found');
        }

        $aspects = $this
            ->getDoctrine()
            ->getRepository('AppBundle:Aspect')
            ->createQueryBuilder('a')
            ->select('a, COALESCE(a.dateExact, a.dateFrom, a.dateTo, 99999) as orderDate')
            ->addSelect("FIELD(a.type, 'beginningOfLife', 'entryInTheOrder', 'resignationFromTheOrder', 'expulsionFromTheOrder', 'endOfLife', 'education', 'career', 'miscellaneous') as HIDDEN typeField")
            ->addOrderBy('typeField', 'ASC')
            ->addOrderBy('orderDate', 'ASC')
            ->where('a.person = :person')
            ->setParameter('person', $person->getId())
            ->getQuery()
            ->execute()
        ;

        return $this->render('default/detail.html.twig', array(
            'person' => $person,
            'aspects' => $aspects
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
                'SELECT s, (SELECT COUNT(DISTINCT p.id) FROM AppBundle:Person p INNER JOIN p.subjects ps WHERE ps.id=s.id) as personCount, (SELECT COUNT(DISTINCT a.id) FROM AppBundle:Aspect a INNER JOIN a.subjects asps WHERE asps.id=s.id) as aspectCount FROM AppBundle:Subject s ORDER BY s.title ASC'
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
            'fullCount' => count($subjects),
            'letters' => $letters
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


}
