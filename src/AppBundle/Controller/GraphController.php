<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Person;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GraphController extends Controller
{
    /**
     * @Route("/graph/", name="graph")
     */
    public function indexAction()
    {
        return $this->render('graph/index.html.twig');
    }

    /**
     * @Route("/graph/relations/", name="relations")
     */
    public function relationsAction()
    {
        /** @var Collection|Person[] $persons */
        $persons = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT p, (select count(r.id) from AppBundle:Relation r where r.source = p.id or r.target = p.id) as nodeValue FROM AppBundle:Person p INNER JOIN p.aspects a'
            )
            ->execute();
        $nodes = array();
        foreach ($persons as $row) {
            $person = $row[0];
            $nodes[] = array(
                'id' => (string)$person->getId(),
                'group' => (int)$person->isJesuit(),
                'label' => $person->getDisplayName(),
                'title' => $person->getDisplayName(),
                'value' => (int)$row['nodeValue']
            );
        }

        $conn = $this->getDoctrine()->getConnection();
        $stmt = $conn->query('SELECT * FROM relations');
        $edges = array();
        while ($row = $stmt->fetch()) {
            $edges[] = array(
                'from' => (string)$row['source_id'],
                'to' => (string)$row['target_id'],
                'label' => $row['value'],
                'arrows' => 'to'
            );
        }
        return $this->render('graph/relations.html.twig', array(
            'nodes' => $nodes,
            'edges' => $edges
        ));
    }
}
