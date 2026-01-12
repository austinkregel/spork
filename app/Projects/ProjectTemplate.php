<?php

declare(strict_types=1);

namespace App\Projects;

enum ProjectTemplate: string
{
    /**
     * Legacy template keys kept for backward compatibility with older stored project settings.
     * These are not necessarily shown in the UI anymore.
     */
    case INFRA_DEPLOYMENT = 'infra_deployment';
    case RESEARCH_HUB = 'research_hub';
    case PERSONAL_UPKEEP = 'personal_upkeep';

    case CUSTOM = 'custom';

    // New templates (8 total shown in UI including CUSTOM)
    case FINANCE_TRACKING = 'finance_tracking';
    case COMMUNICATION_HUB = 'communication_hub';
    case AUTOMATION_OPS = 'automation_ops';
    case INFRASTRUCTURE_MONITORING = 'infrastructure_monitoring';
    case CONTENT_RESEARCH = 'content_research';
    case PERSONAL_CRM = 'personal_crm';
    case HOME_OPS = 'home_ops';
}
