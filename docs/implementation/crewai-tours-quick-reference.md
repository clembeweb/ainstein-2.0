# CrewAI Tours - Quick Reference Card

## Files Created
```
resources/js/tours/crew-launch-tour.js         (394 lines)
resources/js/tours/execution-monitor-tour.js   (521 lines)
```

## Files Modified
```
resources/js/app.js                                  (+18 lines)
resources/views/tenant/crews/show.blade.php          (+14 lines - added "Show Tour" button)
resources/views/tenant/crew-executions/show.blade.php (+14 lines - added "Show Tour" button)
```

---

## Quick Commands

### Build Assets
```bash
cd C:\laragon\www\ainstein-3
npm run build
```

### Clear Tour State (Browser Console)
```javascript
// Clear crew launch tour
localStorage.removeItem('ainstein_tour_crew_launch_completed');

// Clear execution monitor tour
localStorage.removeItem('ainstein_tour_execution_monitor_completed');

// Clear all tours
localStorage.clear();
```

### Manual Start (Browser Console)
```javascript
// Start crew launch tour
window.startCrewLaunchTour();

// Start execution monitor tour
window.startExecutionMonitorTour();
```

---

## Tour 1: Crew Launch

**Trigger**: First visit to `/dashboard/crews/{id}`
**Steps**: 7 steps
**Duration**: ~2-3 minutes

**What it covers**:
1. What AI Crews are
2. Overview tab (agents & tasks)
3. Execute tab (launch interface)
4. Mock vs Real mode
5. JSON input format
6. Launch button
7. History tracking

**Key Features**:
- Auto-switches to Execute tab on step 3
- Provides real JSON examples
- Explains cost implications

---

## Tour 2: Execution Monitor

**Trigger**: First visit to `/dashboard/crew-executions/{id}`
**Steps**: 8 steps
**Duration**: ~2-3 minutes

**What it covers**:
1. Real-time monitoring intro
2. Status badges (5 states)
3. Progress bar (for running)
4. Stats cards (tokens, cost, duration)
5. Live logs with timestamps
6. Auto-refresh toggle
7. Results (copy/download)
8. Actions (cancel/retry)

**Key Features**:
- Adapts to execution status
- Skips missing elements gracefully
- Highlights interactive controls

---

## Testing Quick Check

### ✅ Everything Works If:
- [ ] Assets built without errors (`npm run build`)
- [ ] No console errors on page load
- [ ] "Show Tour" button visible on crew pages
- [ ] "Show Tour" button visible on execution pages
- [ ] Tours auto-start on first visit (clear localStorage first)
- [ ] Element highlighting works on each step
- [ ] Auto-tab switching works on crew launch tour step 3
- [ ] "Don't show again" persists after completion
- [ ] Manual trigger works after tour completion

### 🐛 Troubleshooting
**Tour doesn't start?**
- Clear localStorage: `localStorage.clear()`
- Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Check console for errors
- Verify assets built: check `public/build/manifest.json` timestamp

**Elements not highlighted?**
- Check CSS selectors in tour files
- Verify elements exist on page (DevTools Inspector)
- Wait for Alpine.js to mount (~500ms after page load)

**Button doesn't work?**
- Check `window.startCrewLaunchTour` exists in console
- Verify app.js imports tour files
- Rebuild assets: `npm run build`

---

## Customization Cheat Sheet

### Change Auto-Start Delay
```javascript
// In crew-launch-tour.js (line ~340)
setTimeout(() => {
    initCrewLaunchTour().start();
}, 1000); // Change to 500ms or 2000ms
```

### Disable Auto-Start (Keep Manual Button)
```javascript
// In app.js, comment out:
// autoStartCrewLaunchTour();
// autoStartExecutionMonitorTour();
```

### Change Button Labels
```javascript
// In tour step definitions
buttons: [
    { text: 'Previous', classes: 'shepherd-button-secondary', action: tour.back },
    { text: 'Continue', classes: 'shepherd-button-primary', action: tour.next }
]
```

### Edit Step Content
```javascript
tour.addStep({
    id: 'step-id',
    title: 'Your Custom Title',
    text: `
        <div class="onboarding-content">
            <p>Your custom HTML content here...</p>
            <ul class="list-disc pl-5">
                <li>Point 1</li>
                <li>Point 2</li>
            </ul>
        </div>
    `,
    // ...
});
```

---

## URLs to Test

### Crew Launch Tour
```
http://localhost/dashboard/crews/1
http://localhost/dashboard/crews/2
http://localhost/dashboard/crews/{any-crew-id}
```

### Execution Monitor Tour
```
http://localhost/dashboard/crew-executions/1
http://localhost/dashboard/crew-executions/2
http://localhost/dashboard/crew-executions/{any-execution-id}
```

---

## LocalStorage Keys

```javascript
// Crew launch tour completed
localStorage.getItem('ainstein_tour_crew_launch_completed')
// Returns: "true" or null

// Execution monitor tour completed
localStorage.getItem('ainstein_tour_execution_monitor_completed')
// Returns: "true" or null
```

---

## CSS Classes Used

### Shepherd Theme
```css
.shepherd-theme-custom              /* Main theme class */
.shepherd-element                   /* Tour modal */
.shepherd-modal-overlay-container   /* Dark overlay */
.shepherd-button-primary            /* Blue CTA buttons */
.shepherd-button-secondary          /* Gray secondary buttons */
```

### Content Styling
```css
.onboarding-content                 /* Wraps all step content */
.onboarding-content p               /* Paragraphs */
.onboarding-content strong          /* Bold text */
.onboarding-content ul              /* Lists */
.onboarding-content code            /* Inline code */
```

---

## Step Positioning Options

```javascript
attachTo: {
    element: '.my-selector',
    on: 'top'       // 'top', 'bottom', 'left', 'right', 'auto'
}
```

**Best Practices**:
- `'bottom'` for header elements (tabs, buttons)
- `'top'` for content sections (tables, forms)
- `'left'` or `'right'` for sidebars
- `'auto'` lets Shepherd choose (use when unsure)

---

## Button Configuration

```javascript
buttons: [
    // Back button (secondary style)
    {
        text: 'Back',
        classes: 'shepherd-button-secondary',
        action: tour.back
    },

    // Next button (primary style)
    {
        text: 'Next',
        classes: 'shepherd-button-primary',
        action: tour.next
    },

    // Skip/Cancel button
    {
        text: 'Skip',
        classes: 'shepherd-button-secondary',
        action: tour.cancel
    },

    // Complete button (last step)
    {
        text: 'Got It!',
        classes: 'shepherd-button-primary',
        action: function() {
            // Custom completion logic
            localStorage.setItem('tour_completed', 'true');
            tour.complete();
        }
    }
]
```

---

## Common Patterns

### Auto-Switch Tab
```javascript
{
    text: 'Next',
    action: function() {
        // Click Alpine.js tab button
        const tab = document.querySelector('button[@click*="activeTab = \'execute\'"]');
        if (tab) tab.click();
        setTimeout(() => tour.next(), 300);
    }
}
```

### Conditional Steps
```javascript
// Only show if element exists
if (document.querySelector('.my-element')) {
    tour.addStep({
        // Step definition
    });
}
```

### Skip Missing Elements
```javascript
when: {
    show: function() {
        const element = document.querySelector('.my-element');
        if (!element) {
            tour.next(); // Skip to next step
        }
    }
}
```

---

## Performance Tips

- Keep tours under 10 steps (optimal completion rate)
- Use concise text (under 100 words per step)
- Minimize large images (prefer SVG icons)
- Test on slow connections (3G throttling)
- Avoid heavy animations in tour content

---

## Accessibility Checklist

- [ ] Escape key closes tour
- [ ] Tab key navigates buttons
- [ ] Enter key activates focused button
- [ ] Focus visible on all interactive elements
- [ ] Color contrast meets WCAG AA (4.5:1)
- [ ] Screen reader can read content (test with NVDA/JAWS)
- [ ] Tour works without mouse (keyboard only)

---

## Version History

**v1.0.0** (2025-10-10)
- Initial implementation
- 2 tours, 15 total steps
- Auto-start + manual trigger
- LocalStorage persistence
- Responsive design
- Production-ready

---

## Need Help?

**Documentation**:
- Full details: `CREWAI_ONBOARDING_IMPLEMENTATION.md`
- Testing guide: `CREWAI_ONBOARDING_TESTS.md`
- This file: `CREWAI_TOURS_QUICK_REFERENCE.md`

**Debugging Steps**:
1. Clear browser cache (hard refresh)
2. Clear localStorage
3. Check console for errors
4. Verify assets built (`npm run build`)
5. Test in incognito mode
6. Check element selectors exist in DOM

**Still stuck?**
- Check Shepherd.js docs: https://shepherdjs.dev/
- Review existing tours: `resources/js/onboarding-tools.js`
- Test with minimal tour (1-2 steps)
