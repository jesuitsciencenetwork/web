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
        $colors = [
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

        /** @var Collection|Person[] $persons */
        $persons = $this->getDoctrine()->getManager()
            ->createQuery(
                'SELECT p, (select count(r.id) from AppBundle:Relation r where r.source = p.id or r.target = p.id) as nodeValue FROM AppBundle:Person p INNER JOIN p.aspects a'
            )
            ->execute();
        $nodes = [];
        foreach ($persons as $row) {
            $person = $row[0];
            $nodes[] = [
                'id' => (string)$person->getId(),
                'group' => (int)$person->isJesuit(),
                'label' => $person->getDisplayName(),
                'title' => $person->getDisplayName(),
                'value' => (int)$row['nodeValue']
            ];
        }

        $conn = $this->getDoctrine()->getConnection();
        $stmt = $conn->query('SELECT * FROM relations');
        $edges = [];
        while ($row = $stmt->fetch()) {
            $edges[] = [
                'from' => (string)$row['source_id'],
                'to' => (string)$row['target_id'],
                'title' => $row['value'],
                'arrows' => 'to',
                'color' => $colors[$row['value']],
                'width' => 2,
            ];
        }
        return $this->render('graph/relations.html.twig', [
            'nodes' => $nodes,
            'edges' => $edges
        ]
        );
    }
}
