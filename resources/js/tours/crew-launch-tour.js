/**
 * AINSTEIN Platform - CrewAI Launch Tour
 * Guided tour for first-time users launching AI crews
 */

import Shepherd from 'shepherd.js';

/**
 * Initialize Crew Launch Onboarding Tour
 */
export function initCrewLaunchTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: {
                enabled: true
            }
        }
    });

    // Step 1: Welcome - What are AI Crews
    tour.addStep({
        id: 'crew-welcome',
        title: 'Welcome to AI Crews',
        text: `
            <div class="onboarding-content">
                <p class="mb-4"><strong>AI Crews</strong> are teams of specialized AI agents that work together to accomplish complex tasks.</p>
                <p class="mb-4">Think of them as your virtual workforce:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Multiple agents with different roles and expertise</li>
                    <li>Each agent has access to specific tools</li>
                    <li>Agents collaborate to complete tasks in sequence</li>
                    <li>Perfect for complex workflows like research, writing, and analysis</li>
                </ul>
                <p class="text-xs text-gray-500 mt-4">Let's explore how to launch and monitor your first crew execution!</p>
            </div>
        `,
        buttons: [
            {
                text: 'Skip Tour',
                classes: 'shepherd-button-secondary',
                action: tour.cancel
            },
            {
                text: 'Get Started',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 2: Overview Tab - Show agents and tasks
    tour.addStep({
        id: 'crew-overview-tab',
        title: 'Overview: Your Crew Structure',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>Overview</strong> tab shows your crew's structure:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Agents:</strong> The AI team members with specific roles (researcher, writer, reviewer)</li>
                    <li><strong>Tasks:</strong> The jobs assigned to each agent</li>
                    <li><strong>Tools:</strong> Resources agents can use (web search, file access, etc.)</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">Each agent knows its role and the order in which tasks are executed matters!</p>
            </div>
        `,
        attachTo: {
            element: 'button[\\@click*="activeTab = \'overview\'"]',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 3: Execute Tab - Main launch interface
    tour.addStep({
        id: 'crew-execute-tab',
        title: 'Execute: Launch Your Crew',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>Execute</strong> tab is where the magic happens!</p>
                <p class="mb-3">This is your mission control for launching crew executions.</p>
                <p class="text-sm mb-3">Here you'll configure:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li>Execution mode (Mock or Real)</li>
                    <li>Input variables for your crew</li>
                    <li>Launch parameters</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">Click the Execute tab to see the launch interface</p>
            </div>
        `,
        attachTo: {
            element: 'button[\\@click*="activeTab = \'execute\'"]',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: function() {
                    // Auto-switch to Execute tab
                    const executeTab = document.querySelector('button[\\@click*="activeTab = \'execute\'"]');
                    if (executeTab) executeTab.click();
                    setTimeout(() => tour.next(), 300);
                }
            }
        ]
    });

    // Step 4: Mock vs Real Mode - Critical concept
    tour.addStep({
        id: 'crew-execution-mode',
        title: 'Execution Mode: Mock vs Real',
        text: `
            <div class="onboarding-content">
                <p class="mb-3"><strong>Choose your execution mode wisely:</strong></p>

                <div class="mb-4">
                    <div class="flex items-start mb-3">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <strong class="text-blue-900">Mock Mode</strong>
                            <p class="text-sm text-gray-600 mt-1">Test run without using real API tokens. Perfect for testing your crew configuration and workflow. No cost, simulated results.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="w-3 h-3 bg-green-500 rounded-full mt-1 mr-3"></div>
                        <div>
                            <strong class="text-green-900">Real Mode</strong>
                            <p class="text-sm text-gray-600 mt-1">Live execution using actual AI models. Consumes API tokens and produces real results. Use this when you're ready for production.</p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded p-2">
                    Always test with Mock Mode first to verify your inputs and configuration!
                </p>
            </div>
        `,
        attachTo: {
            element: 'div.grid.grid-cols-2.gap-4',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 5: JSON Input - How to provide variables
    tour.addStep({
        id: 'crew-json-input',
        title: 'Input Variables: JSON Format',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Your crew needs <strong>input variables</strong> to work with.</p>
                <p class="mb-3 text-sm">Provide them in JSON format:</p>

                <div class="bg-gray-900 text-gray-100 rounded p-3 mb-3 font-mono text-xs">
{
  "topic": "AI Marketing Strategies",
  "target": "SMB owners",
  "tone": "professional yet friendly",
  "word_count": "1500"
}
                </div>

                <p class="text-sm mb-2"><strong>Common variables:</strong></p>
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    <li><code>topic</code> - Main subject to research/write about</li>
                    <li><code>target</code> - Target audience</li>
                    <li><code>tone</code> - Writing style</li>
                    <li><code>word_count</code> - Desired length</li>
                </ul>

                <p class="text-xs text-gray-500 mt-3">The JSON validator will check your syntax in real-time</p>
            </div>
        `,
        attachTo: {
            element: '#input_variables',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 6: Launch Button - Start execution
    tour.addStep({
        id: 'crew-launch-button',
        title: 'Launch Your First Execution',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ready to launch? Click the <strong>Launch Execution</strong> button!</p>

                <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-3">
                    <p class="text-sm mb-2"><strong>What happens next:</strong></p>
                    <ol class="list-decimal pl-5 space-y-1 text-xs">
                        <li>Your JSON is validated</li>
                        <li>A new execution is created</li>
                        <li>You're redirected to the execution monitor</li>
                        <li>Agents start working on tasks</li>
                        <li>You can watch progress in real-time</li>
                    </ol>
                </div>

                <p class="text-xs text-gray-500">First time? Start with Mock Mode to see how it works!</p>
            </div>
        `,
        attachTo: {
            element: 'button[\\@click*="launchExecution"]',
            on: 'top'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 7: History Tab - Past executions
    tour.addStep({
        id: 'crew-history-tab',
        title: 'History: Track Your Executions',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>History</strong> tab shows your last 10 executions.</p>

                <p class="text-sm mb-3">For each execution you'll see:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Status:</strong> Completed, Running, Failed, or Cancelled</li>
                    <li><strong>Duration:</strong> How long it took to complete</li>
                    <li><strong>Tokens:</strong> API tokens consumed</li>
                    <li><strong>Started At:</strong> When it was launched</li>
                </ul>

                <p class="text-xs text-gray-500 mt-3">Click "View Details" on any execution to see full logs and results</p>
            </div>
        `,
        attachTo: {
            element: 'button[\\@click*="activeTab = \'history\'"]',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Next',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Final Step: Completion
    tour.addStep({
        id: 'crew-tour-complete',
        title: 'You\'re Ready to Launch!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4"><strong>Congratulations!</strong> You now know how to launch AI crews.</p>

                <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                    <p class="text-sm mb-2"><strong>Quick Recap:</strong></p>
                    <ol class="list-decimal pl-5 space-y-1 text-xs">
                        <li><strong>Overview:</strong> See your crew structure</li>
                        <li><strong>Execute:</strong> Choose Mock/Real mode</li>
                        <li><strong>JSON Input:</strong> Provide variables</li>
                        <li><strong>Launch:</strong> Start execution</li>
                        <li><strong>History:</strong> Track all executions</li>
                    </ol>
                </div>

                <p class="text-sm mb-4">Next, you'll learn about the <strong>Execution Monitor</strong> where you can watch your crew work in real-time!</p>

                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-crew-launch-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Don't show this tour again</span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            {
                text: 'Back',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Got It!',
                classes: 'shepherd-button-primary',
                action: function() {
                    const dontShow = document.getElementById('dont-show-crew-launch-again');
                    if (dontShow && dontShow.checked) {
                        localStorage.setItem('ainstein_tour_crew_launch_completed', 'true');
                    }
                    tour.complete();
                }
            }
        ]
    });

    return tour;
}

/**
 * Auto-start tour if not completed
 */
export function autoStartCrewLaunchTour() {
    const completed = localStorage.getItem('ainstein_tour_crew_launch_completed');
    if (!completed && window.location.pathname.includes('/crews/') && !window.location.pathname.includes('/executions')) {
        // Delay to ensure page is fully loaded
        setTimeout(() => {
            initCrewLaunchTour().start();
        }, 1000);
    }
}

/**
 * Manual tour start (from button)
 */
export function startCrewLaunchTour() {
    initCrewLaunchTour().start();
}

// Make globally available
window.startCrewLaunchTour = startCrewLaunchTour;
