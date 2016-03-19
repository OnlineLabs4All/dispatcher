<?php
/**
 * Created by PhpStorm.
 * User: DAnilo G. Zutin
 * Date: 19.03.16
 * Time: 09:42
 */

namespace DispatcherBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/secured/ui/api")
 */
class UiApiController extends Controller
{
    /**
     * @Route("/deleteResource", name="deleteResource")
     */
    public function deleteJobRecord(Request $request)
    {
        $dashboadServices = $this->get('dashboardUiServices');
        $user = $this->getUser();
        $jobRecords = $request->request->all();

        foreach ($jobRecords as $jobRecord) {
            //var_dump($jobRecord);
            $dashboadServices->deleteJobRecord($jobRecord, $user);
        }

        //return new Response();
        return $this->redirectToRoute('expRecords');
    }

    /**
     * @Route("/changeJobStatus/{newStatus}", name="changeJobStatus")
     */
    public function changeJobStatus(Request $request, $newStatus)
    {
        $dashboadServices = $this->get('dashboardUiServices');
        $user = $this->getUser();
        $expIds = $request->request->all();

        foreach ($expIds as $expId){
            //var_dump($jobRecord);
            $dashboadServices->changeJobStatus($expId, $newStatus, $user);
        }

        //return new Response();
        return $this->redirectToRoute('expRecords');
    }
}