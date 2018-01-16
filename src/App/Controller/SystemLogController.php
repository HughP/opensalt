<?php

namespace App\Controller;

use App\Repository\ChangeEntryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemLogController extends AbstractController
{
    /**
     * @var AuditReader
     */
    protected $auditReader;

    /**
     * @var ChangeEntryRepository
     */
    private $entryRepository;

    public function __construct(AuditReader $auditReader, ChangeEntryRepository $entryRepository)
    {
        $this->auditReader = $auditReader;
        $this->entryRepository = $entryRepository;
    }

    /**
     * @Route("/systemLogs", name="system_logs_show")
     * @Method({"GET"})
     * @Security("is_granted('ROLE_SUPER_USER')")
     *
     * @return Response
     */
    public function showSystemLogs(): Response
    {
        return $this->render('system_logs/show.html.twig');
    }

    /**
     * @Route("/systemLogs/revisions/{offset}/{limit}", defaults={"offset" = 0, "limit" = 0}, name="system_logs_json")
     * @Method({"GET"})
     * @Security("is_granted('ROLE_SUPER_USER')")
     */
    public function listDocRevisionsAction(int $offset = 0, int $limit = 0): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'application/json');

        $response->setCallback(function () use ($limit, $offset) {
            $fd = fopen('php://output', 'wb+');
            fwrite($fd, '{"data": [');

            $history = $this->entryRepository->getChangeEntriesForSystem($limit, $offset);

            $first = true;
            foreach ($history as $line) {
                if (!$first) {
                    fwrite($fd, ',');
                } else {
                    $first = false;
                }
                fwrite($fd, json_encode([
                    'changed_at' => $line['changed_at'],
                    'description' => $line['description'],
                    'username' => $line['username']
                ]));
            }

            fwrite($fd, ']}');
            fclose($fd);
        });

        return $response;
    }

    /**
     * @Route("/systemLogs/export", name="system_logs_csv")
     * @Method({"GET"})
     * @Security("is_granted('ROLE_SUPER_USER')")
     *
     * @return Response
     */
    public function exportSystemLogs(): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="system_log.csv"');

        $response->setCallback(function () {
            $fd = fopen('php://output', 'wb+');

            fputcsv($fd, ['Date', 'Description', 'Username']);

            $history = $this->entryRepository->getChangeEntriesForSystem(0, 0);
            foreach ($history as $line) {
                fputcsv($fd, [
                    preg_replace('/\..*$/', '', $line['changed_at']),
                    $line['description'],
                    $line['username']
                ]);
            }

            fclose($fd);
        });

        return $response;
    }
}
