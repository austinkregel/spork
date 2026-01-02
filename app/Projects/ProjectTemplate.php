<?php

declare(strict_types=1);

namespace App\Projects;

enum ProjectTemplate: string
{
    case INFRA_DEPLOYMENT = 'infra_deployment';
    case RESEARCH_HUB = 'research_hub';
    case PERSONAL_UPKEEP = 'personal_upkeep';
    case CUSTOM = 'custom';
}


