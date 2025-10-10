/**
 * AINSTEIN Platform - Execution Monitor Tour
 * Guided tour for first-time users viewing crew execution details
 */

import Shepherd from 'shepherd.js';

/**
 * Initialize Execution Monitor Onboarding Tour
 */
export function initExecutionMonitorTour() {
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

    // Step 1: Welcome to Real-time Monitoring
    tour.addStep({
        id: 'exec-welcome',
        title: 'Real-Time Execution Monitor',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Welcome to the <strong>Execution Monitor</strong> - your mission control for watching AI crews work!</p>
                <p class="mb-4">This powerful interface gives you:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Live logs:</strong> See what agents are doing in real-time</li>
                    <li><strong>Progress tracking:</strong> Visual progress bar during execution</li>
                    <li><strong>Performance metrics:</strong> Tokens, cost, and duration stats</li>
                    <li><strong>Results:</strong> Final output when execution completes</li>
                    <li><strong>Controls:</strong> Cancel, retry, and manage executions</li>
                </ul>
                <p class="text-xs text-gray-500 mt-4">Let's explore each feature!</p>
            </div>
        `,
        buttons: [
            {
                text: 'Skip Tour',
                classes: 'shepherd-button-secondary',
                action: tour.cancel
            },
            {
                text: 'Show Me',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 2: Status Badge - Understanding execution states
    tour.addStep({
        id: 'exec-status-badge',
        title: 'Execution Status',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>status badge</strong> shows the current execution state:</p>

                <div class="space-y-3 mb-4">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 mr-3">
                            <span class="w-2 h-2 bg-yellow-600 rounded-full mr-1.5"></span>
                            Pending
                        </span>
                        <span class="text-sm">Waiting to start</span>
                    </div>

                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 mr-3">
                            <span class="w-2 h-2 bg-blue-600 rounded-full mr-1.5 animate-pulse"></span>
                            Running
                        </span>
                        <span class="text-sm">Agents are working</span>
                    </div>

                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 mr-3">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-1.5"></span>
                            Completed
                        </span>
                        <span class="text-sm">Successfully finished</span>
                    </div>

                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 mr-3">
                            <span class="w-2 h-2 bg-red-600 rounded-full mr-1.5"></span>
                            Failed
                        </span>
                        <span class="text-sm">Error occurred</span>
                    </div>

                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 mr-3">
                            <span class="w-2 h-2 bg-gray-600 rounded-full mr-1.5"></span>
                            Cancelled
                        </span>
                        <span class="text-sm">Manually stopped</span>
                    </div>
                </div>

                <p class="text-xs text-gray-500">The badge updates automatically as execution progresses</p>
            </div>
        `,
        attachTo: {
            element: '.inline-flex.items-center.px-4.py-2.rounded-full',
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

    // Step 3: Progress Bar - Live updates
    tour.addStep({
        id: 'exec-progress-bar',
        title: 'Live Progress Tracking',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">For <strong>running</strong> executions, watch the progress bar fill up!</p>

                <div class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                        <div class="bg-blue-600 h-3 rounded-full animate-pulse" style="width: 65%"></div>
                    </div>
                    <p class="text-xs text-gray-500 text-center">Example: 65% complete</p>
                </div>

                <p class="text-sm mb-3">The progress updates as agents complete tasks:</p>
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    <li>0-25%: Research phase</li>
                    <li>25-50%: Writing phase</li>
                    <li>50-75%: Review phase</li>
                    <li>75-100%: Finalization</li>
                </ul>

                <p class="text-xs text-gray-500 mt-3">The bar pulses to show active processing</p>
            </div>
        `,
        attachTo: {
            element: '.w-full.bg-gray-200.rounded-full.h-3',
            on: 'top'
        },
        when: {
            show: function() {
                // Only show if progress bar exists
                const progressBar = document.querySelector('.w-full.bg-gray-200.rounded-full.h-3');
                if (!progressBar) {
                    tour.next();
                }
            }
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

    // Step 4: Stats Cards - Understanding metrics
    tour.addStep({
        id: 'exec-stats-cards',
        title: 'Performance Metrics',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Three key metrics track your execution:</p>

                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                            </svg>
                        </div>
                        <div>
                            <strong>Tokens Used</strong>
                            <p class="text-xs text-gray-600">API tokens consumed by AI models. More tokens = longer/more complex content.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <strong>Cost</strong>
                            <p class="text-xs text-gray-600">Estimated cost based on tokens and AI model pricing. Helps you budget.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <strong>Duration</strong>
                            <p class="text-xs text-gray-600">Total execution time. Typically 30 seconds to 5 minutes depending on complexity.</p>
                        </div>
                    </div>
                </div>
            </div>
        `,
        attachTo: {
            element: '.grid.grid-cols-1.md\\:grid-cols-3.gap-4',
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

    // Step 5: Logs Section - Reading execution output
    tour.addStep({
        id: 'exec-logs-section',
        title: 'Execution Logs: Watch It Work',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>Execution Logs</strong> section is where you see the magic happen!</p>

                <p class="text-sm mb-3">Logs show you:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Agent actions:</strong> "Researcher starting task..."</li>
                    <li><strong>Tool usage:</strong> "Using web_search tool..."</li>
                    <li><strong>Task completion:</strong> "Task 1 completed successfully"</li>
                    <li><strong>Errors:</strong> Red badges for problems</li>
                    <li><strong>Timestamps:</strong> When each action occurred</li>
                </ul>

                <div class="bg-gray-900 text-gray-100 rounded p-2 mt-3 font-mono text-xs">
                    <div class="flex space-x-2">
                        <span class="text-gray-500">14:32:15</span>
                        <span class="px-2 py-0.5 bg-blue-900 text-blue-200 rounded">INFO</span>
                        <span>Agent starting research task...</span>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-3">Logs auto-scroll to the bottom as new entries appear</p>
            </div>
        `,
        attachTo: {
            element: '.bg-gray-900.text-gray-100.font-mono',
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

    // Step 6: Auto-refresh Toggle
    tour.addStep({
        id: 'exec-auto-refresh',
        title: 'Auto-Refresh Control',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">The <strong>Auto-refresh</strong> toggle controls live updates:</p>

                <div class="space-y-3 text-sm mb-4">
                    <div class="flex items-start">
                        <input type="checkbox" checked disabled class="rounded border-gray-300 text-blue-600 mt-1 mr-3">
                        <div>
                            <strong>Enabled (default)</strong>
                            <p class="text-xs text-gray-600">Logs update every 2 seconds automatically. Best for monitoring running executions.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 mt-1 mr-3">
                        <div>
                            <strong>Disabled</strong>
                            <p class="text-xs text-gray-600">Pauses automatic updates. Useful if you want to read logs without them scrolling.</p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500">Auto-refresh stops automatically when execution completes</p>
            </div>
        `,
        attachTo: {
            element: 'input[type="checkbox"][x-model*="autoRefresh"]',
            on: 'left'
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

    // Step 7: Results Section - Final output
    tour.addStep({
        id: 'exec-results-section',
        title: 'Final Results',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">When execution <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Completes</span>, you'll see the <strong>Final Results</strong> section:</p>

                <div class="bg-white border border-gray-200 rounded p-3 mb-3">
                    <p class="text-sm mb-2"><strong>What you get:</strong></p>
                    <ul class="list-disc pl-5 space-y-1 text-xs">
                        <li>Complete output from all agents</li>
                        <li>Formatted and ready to use</li>
                        <li>Copy button for quick clipboard access</li>
                        <li>Download button to save as text file</li>
                    </ul>
                </div>

                <p class="text-sm mb-3"><strong>Quick actions:</strong></p>
                <div class="flex space-x-2">
                    <button class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded text-xs">
                        Copy
                    </button>
                    <button class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded text-xs">
                        Download
                    </button>
                </div>

                <p class="text-xs text-gray-500 mt-3">Results persist even after you leave the page</p>
            </div>
        `,
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

    // Step 8: Action Buttons - Cancel and Retry
    tour.addStep({
        id: 'exec-action-buttons',
        title: 'Execution Controls',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Take control with action buttons:</p>

                <div class="space-y-3 text-sm mb-4">
                    <div class="flex items-start">
                        <button class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded text-xs mr-3 flex-shrink-0">
                            Cancel
                        </button>
                        <div>
                            <strong>Cancel Execution</strong>
                            <p class="text-xs text-gray-600">Appears for <span class="text-blue-600">Running</span> executions. Stops the crew immediately. Use if execution is stuck or taking too long.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded text-xs mr-3 flex-shrink-0">
                            Retry
                        </button>
                        <div>
                            <strong>Retry Execution</strong>
                            <p class="text-xs text-gray-600">Appears for <span class="text-red-600">Failed</span> or <span class="text-gray-600">Cancelled</span> executions. Launches a new execution with same inputs.</p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded p-2">
                    Note: Cancelling an execution still consumes tokens up to the cancellation point
                </p>
            </div>
        `,
        attachTo: {
            element: '.flex.flex-wrap.gap-2.mt-6.pt-6.border-t',
            on: 'top'
        },
        when: {
            show: function() {
                // Only show if action buttons exist
                const actionButtons = document.querySelector('.flex.flex-wrap.gap-2.mt-6.pt-6.border-t');
                if (!actionButtons) {
                    tour.next();
                }
            }
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
        id: 'exec-tour-complete',
        title: 'You\'re an Execution Pro!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4"><strong>Excellent!</strong> You now know how to monitor crew executions like a pro.</p>

                <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
                    <p class="text-sm mb-2"><strong>Quick Reference:</strong></p>
                    <ul class="list-disc pl-5 space-y-1 text-xs">
                        <li><strong>Status badge:</strong> Current execution state</li>
                        <li><strong>Progress bar:</strong> Visual completion indicator</li>
                        <li><strong>Stats cards:</strong> Tokens, cost, duration</li>
                        <li><strong>Logs:</strong> Real-time agent activity</li>
                        <li><strong>Auto-refresh:</strong> Toggle live updates</li>
                        <li><strong>Results:</strong> Final output (when completed)</li>
                        <li><strong>Actions:</strong> Cancel running, retry failed</li>
                    </ul>
                </div>

                <div class="bg-green-50 border border-green-200 rounded p-3 mb-4">
                    <p class="text-sm"><strong>Pro Tips:</strong></p>
                    <ul class="list-disc pl-5 space-y-1 text-xs mt-2">
                        <li>Watch logs to understand how agents think</li>
                        <li>Check token usage to optimize prompts</li>
                        <li>Retry failed executions with adjusted inputs</li>
                        <li>Keep execution monitor open during runs</li>
                    </ul>
                </div>

                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-exec-monitor-again" class="rounded border-gray-300 text-blue-600 mr-2">
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
                text: 'Finish',
                classes: 'shepherd-button-primary',
                action: function() {
                    const dontShow = document.getElementById('dont-show-exec-monitor-again');
                    if (dontShow && dontShow.checked) {
                        localStorage.setItem('ainstein_tour_execution_monitor_completed', 'true');
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
export function autoStartExecutionMonitorTour() {
    const completed = localStorage.getItem('ainstein_tour_execution_monitor_completed');
    if (!completed && window.location.pathname.includes('/crew-executions/')) {
        // Delay to ensure page is fully loaded
        setTimeout(() => {
            initExecutionMonitorTour().start();
        }, 1000);
    }
}

/**
 * Manual tour start (from button)
 */
export function startExecutionMonitorTour() {
    initExecutionMonitorTour().start();
}

// Make globally available
window.startExecutionMonitorTour = startExecutionMonitorTour;
