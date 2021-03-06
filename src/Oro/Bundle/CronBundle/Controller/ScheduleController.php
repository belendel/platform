<?php

namespace Oro\Bundle\CronBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/schedule")
 */
class ScheduleController extends Controller
{
    /**
     * @Template
     * @Route("/", name="oro_cron_schedule_index")
     * @Acl(
     *      id="oro_cron_schedule_view",
     *      type="entity",
     *      class="OroCronBundle:Schedule",
     *      permission="VIEW"
     * )
     */
    public function indexAction()
    {
        return [];
    }
}
