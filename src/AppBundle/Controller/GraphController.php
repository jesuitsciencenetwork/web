<?php

namespace AppBundle\Controller;

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
     * @Route("/graph/relations/", name="graph_relations")
     */
    public function relationsAction()
    {
        $persons = $this->getDoctrine()->getManager()->createQuery('SELECT p FROM AppBundle:Person p INNER JOIN p.aspects a WITH a.country = :country')->setParameter('country', 'PL')->execute();
        $nodes = array();
        foreach ($persons as $person) {
//            $person = $row[0];
            $nodes[] = array(
                'id' => (string)$person->getId(),
                'label' => $person->getDisplayName(),
                'title' => $person->getDisplayName(),
//                'value' => (int)$row['nodeValue']
            );
        }

        $conn = $this->getDoctrine()->getConnection();
        $stmt = $conn->query('SELECT * FROM relations');
        $edges = array();
        while ($row = $stmt->fetch()) {
            $edges[] = array(
                'from' => (string)$row['source_id'],
                'to' => (string)$row['target_id'],
                'label' => $row['class']."/".$row['context']."/".$row['value']
            );
        }
        return $this->render('graph/relations.html.twig', array(
            'nodes' => $nodes,
            'edges' => $edges
        ));
    }
}
