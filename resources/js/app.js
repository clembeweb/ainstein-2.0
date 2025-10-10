import './bootstrap';

import Alpine from 'alpinejs';
import Shepherd from 'shepherd.js';
import { initOnboardingTour, autoStartOnboarding, startManualTour } from './onboarding';
import {
    initPagesOnboardingTour,
    initContentGenerationOnboardingTour,
    initPromptsOnboardingTour,
    initApiKeysOnboardingTour,
    autoStartToolOnboarding
} from './onboarding-tools';
import {
    initCrewLaunchTour,
    autoStartCrewLaunchTour,
    startCrewLaunchTour
} from './tours/crew-launch-tour';
import {
    initExecutionMonitorTour,
    autoStartExecutionMonitorTour,
    startExecutionMonitorTour
} from './tours/execution-monitor-tour';

window.Alpine = Alpine;
window.Shepherd = Shepherd;

// Dashboard onboarding
window.initOnboardingTour = initOnboardingTour;
window.autoStartOnboarding = autoStartOnboarding;
window.startOnboardingTour = startManualTour;

// Tool-specific onboarding
window.initPagesOnboardingTour = initPagesOnboardingTour;
window.initContentGenerationOnboardingTour = initContentGenerationOnboardingTour;
window.initPromptsOnboardingTour = initPromptsOnboardingTour;
window.initApiKeysOnboardingTour = initApiKeysOnboardingTour;

// CrewAI tours
window.initCrewLaunchTour = initCrewLaunchTour;
window.startCrewLaunchTour = startCrewLaunchTour;
window.initExecutionMonitorTour = initExecutionMonitorTour;
window.startExecutionMonitorTour = startExecutionMonitorTour;

// Auto-start tool onboarding based on current page
autoStartToolOnboarding();

// Auto-start CrewAI tours
autoStartCrewLaunchTour();
autoStartExecutionMonitorTour();

Alpine.start();
