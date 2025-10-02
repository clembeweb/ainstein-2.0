import './bootstrap';

import Alpine from 'alpinejs';
import Shepherd from 'shepherd.js';
import { initOnboardingTour, autoStartOnboarding, startManualTour } from './onboarding';

window.Alpine = Alpine;
window.Shepherd = Shepherd;
window.initOnboardingTour = initOnboardingTour;
window.autoStartOnboarding = autoStartOnboarding;
window.startOnboardingTour = startManualTour;

Alpine.start();
