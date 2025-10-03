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

// Auto-start tool onboarding based on current page
autoStartToolOnboarding();

Alpine.start();
