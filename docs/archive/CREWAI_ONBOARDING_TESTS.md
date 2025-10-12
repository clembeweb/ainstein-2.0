# CrewAI Onboarding Tours - Testing Checklist

## Overview
This document provides a comprehensive testing checklist for the newly implemented CrewAI onboarding tours.

## Tours Implemented

### 1. Crew Launch Tour (`crew-launch-tour.js`)
- **Target**: First-time users visiting crew detail pages
- **URL Pattern**: `/dashboard/crews/{id}`
- **LocalStorage Key**: `ainstein_tour_crew_launch_completed`
- **Steps**: 7 steps covering crew structure, execution modes, JSON input, and history

### 2. Execution Monitor Tour (`execution-monitor-tour.js`)
- **Target**: First-time users viewing execution details
- **URL Pattern**: `/dashboard/crew-executions/{id}`
- **LocalStorage Key**: `ainstein_tour_execution_monitor_completed`
- **Steps**: 8 steps covering status monitoring, logs, metrics, and controls

---

## Pre-Testing Requirements

### 1. Assets Build
```bash
cd C:\laragon\www\ainstein-3
npm run build
```

**Expected Output:**
```
✓ 59 modules transformed
✓ built in ~13s
```

### 2. Browser Cache Clear
- Clear browser cache and localStorage
- Open DevTools Console to monitor for errors
- Ensure no JavaScript errors on page load

### 3. Test Data Setup
- Create at least one crew with agents and tasks
- Launch at least one execution (Mock mode is fine)
- Verify crew detail page loads correctly
- Verify execution detail page loads correctly

---

## Testing Scenarios

### Tour 1: Crew Launch Tour

#### Test 1.1: Auto-Start on First Visit
**Steps:**
1. Clear localStorage: `localStorage.removeItem('ainstein_tour_crew_launch_completed')`
2. Navigate to any crew detail page: `/dashboard/crews/{id}`
3. Wait 1 second

**Expected Result:**
- Tour starts automatically
- Modal overlay appears with darkened background
- Step 1 "Welcome to AI Crews" is displayed
- Tour is centered and responsive

**Pass Criteria:**
- [ ] Tour auto-starts after 1 second delay
- [ ] Modal overlay is visible (semi-transparent black)
- [ ] Tour content is readable and properly styled
- [ ] "Skip Tour" and "Get Started" buttons are visible

---

#### Test 1.2: Step Navigation
**Steps:**
1. Start the tour (auto-start or manual)
2. Click "Next" through all 7 steps
3. Verify each step targets the correct element

**Expected Steps:**
1. **Welcome** - No element highlight, centered modal
2. **Overview Tab** - Highlights Overview tab button
3. **Execute Tab** - Highlights Execute tab button, auto-switches tab
4. **Execution Mode** - Highlights Mock/Real mode selector
5. **JSON Input** - Highlights textarea with JSON example
6. **Launch Button** - Highlights "Launch Execution" button
7. **History Tab** - Highlights History tab button
8. **Completion** - Final step with "Don't show again" checkbox

**Pass Criteria:**
- [ ] All steps display correct titles and content
- [ ] Element highlighting works correctly
- [ ] Auto-tab switching works on step 3
- [ ] Navigation buttons (Back/Next) work correctly
- [ ] No JavaScript errors in console

---

#### Test 1.3: Skip and Cancel
**Steps:**
1. Start tour
2. Click "Skip Tour" button on first step

**Expected Result:**
- Tour closes immediately
- localStorage key is NOT set (tour can restart)
- Page functions normally

**Pass Criteria:**
- [ ] Tour closes completely
- [ ] No visual artifacts remain
- [ ] localStorage remains empty
- [ ] "Show Tour" button still works

---

#### Test 1.4: Complete with "Don't Show Again"
**Steps:**
1. Start tour
2. Navigate to final step
3. Check "Don't show this tour again" checkbox
4. Click "Got It!"
5. Refresh page

**Expected Result:**
- Tour completes
- localStorage key `ainstein_tour_crew_launch_completed` is set to `"true"`
- Tour does NOT auto-start on refresh
- "Show Tour" button still manually starts tour

**Pass Criteria:**
- [ ] localStorage key is properly set
- [ ] Tour doesn't auto-start after completion
- [ ] Manual "Show Tour" button still works
- [ ] Checkbox preference is respected

---

#### Test 1.5: Manual Tour Trigger
**Steps:**
1. Complete tour with "Don't show again"
2. Click blue "Show Tour" button in top-right

**Expected Result:**
- Tour starts immediately regardless of localStorage
- All steps work correctly
- Tour can be completed again

**Pass Criteria:**
- [ ] Manual trigger always works
- [ ] Tour functions identically to auto-start
- [ ] No errors in console

---

#### Test 1.6: Responsive Design
**Steps:**
1. Start tour
2. Resize browser to mobile (375px width)
3. Navigate through all steps
4. Resize to tablet (768px width)
5. Resize to desktop (1920px width)

**Expected Result:**
- Tour adapts to viewport size
- Content remains readable at all sizes
- Element highlighting adjusts correctly
- No overlapping or cut-off content

**Pass Criteria:**
- [ ] Mobile (375px): Tour fits screen, readable text
- [ ] Tablet (768px): Tour properly positioned
- [ ] Desktop (1920px): Tour centered, appropriate size
- [ ] Element highlights visible at all sizes

---

### Tour 2: Execution Monitor Tour

#### Test 2.1: Auto-Start on First Visit
**Steps:**
1. Clear localStorage: `localStorage.removeItem('ainstein_tour_execution_monitor_completed')`
2. Navigate to any execution detail page: `/dashboard/crew-executions/{id}`
3. Wait 1 second

**Expected Result:**
- Tour starts automatically
- Step 1 "Real-Time Execution Monitor" is displayed
- Modal overlay appears

**Pass Criteria:**
- [ ] Tour auto-starts correctly
- [ ] First step content is clear and engaging
- [ ] Visual design matches crew launch tour

---

#### Test 2.2: Status-Specific Steps
**Steps:**
1. Test on execution with status = "running"
   - Verify progress bar step appears
   - Verify cancel button step appears
2. Test on execution with status = "completed"
   - Verify results section step appears
3. Test on execution with status = "failed"
   - Verify retry button step appears

**Expected Result:**
- Steps adapt to execution status
- Missing elements are gracefully skipped
- Tour flow makes sense for current state

**Pass Criteria:**
- [ ] Running execution: Progress bar + cancel steps visible
- [ ] Completed execution: Results section highlighted
- [ ] Failed execution: Retry button highlighted
- [ ] Tour skips steps for missing elements

---

#### Test 2.3: Element Targeting
**Steps:**
1. Start tour
2. Verify each step highlights correct element:
   - Status badge (colored pill at top)
   - Progress bar (if running)
   - Stats cards (3 gradient cards)
   - Logs section (dark terminal-style area)
   - Auto-refresh toggle (checkbox)
   - Results section (if completed)
   - Action buttons (cancel/retry)

**Expected Result:**
- Each element is highlighted with Shepherd's spotlight
- Tooltip arrow points to correct element
- Scrolling adjusts to bring element into view

**Pass Criteria:**
- [ ] Status badge properly highlighted
- [ ] Stats cards highlighted as group
- [ ] Logs section clearly indicated
- [ ] Toggle checkbox visible and highlighted
- [ ] All targets found without errors

---

#### Test 2.4: Live Execution Interaction
**Steps:**
1. Launch a new crew execution (Real or Mock)
2. Immediately start the monitor tour
3. Navigate through tour while execution runs
4. Observe logs updating during tour

**Expected Result:**
- Tour remains stable during live updates
- Log polling doesn't interfere with tour
- Alpine.js state updates work correctly
- No visual glitches or overlay issues

**Pass Criteria:**
- [ ] Tour navigation remains smooth
- [ ] Logs update in background
- [ ] No JavaScript errors during updates
- [ ] Tour completes successfully

---

#### Test 2.5: Copy/Download Actions
**Steps:**
1. Navigate to completed execution
2. Start tour and reach results section step
3. Note copy and download buttons highlighted
4. Complete tour
5. Test actual copy/download functionality

**Expected Result:**
- Tour highlights interactive elements correctly
- Copy button works after tour
- Download button works after tour
- No interference with Alpine.js functionality

**Pass Criteria:**
- [ ] Copy button highlighted during tour
- [ ] Download button highlighted during tour
- [ ] Both buttons functional after tour
- [ ] Toast notifications work correctly

---

### Cross-Tour Testing

#### Test 3.1: Tour Sequence Flow
**Steps:**
1. Clear all localStorage
2. Visit crew detail page → complete launch tour
3. Launch execution → complete monitor tour
4. Return to crew page → verify no auto-start
5. Visit another execution → verify no auto-start

**Expected Result:**
- Each tour completes independently
- Completion state persists across navigation
- No conflicts between tours

**Pass Criteria:**
- [ ] Both tours complete successfully
- [ ] localStorage keys set correctly
- [ ] No tour conflicts or overlaps
- [ ] State persists across page loads

---

#### Test 3.2: Browser Compatibility
**Steps:**
1. Test in Chrome (latest)
2. Test in Firefox (latest)
3. Test in Edge (latest)
4. Test in Safari (if available)

**Expected Result:**
- Tours work identically in all browsers
- CSS styling renders correctly
- JavaScript functionality consistent

**Pass Criteria:**
- [ ] Chrome: Full functionality
- [ ] Firefox: Full functionality
- [ ] Edge: Full functionality
- [ ] Safari: Full functionality (if tested)

---

#### Test 3.3: Accessibility
**Steps:**
1. Start tour
2. Try keyboard navigation (Tab, Enter, Escape)
3. Use screen reader (if available)
4. Check ARIA labels

**Expected Result:**
- Escape key closes tour
- Tab navigates between buttons
- Enter activates focused button
- Screen reader can read content

**Pass Criteria:**
- [ ] Escape key closes tour
- [ ] Tab navigation works
- [ ] Enter key activates buttons
- [ ] Focus states visible
- [ ] ARIA labels present (check in DevTools)

---

## Performance Testing

### Test 4.1: Load Time Impact
**Steps:**
1. Open DevTools Network tab
2. Clear cache
3. Load crew detail page
4. Measure bundle size and load time

**Expected Result:**
- Total JavaScript bundle < 300KB (gzipped)
- Page load time < 2 seconds
- No blocking scripts

**Pass Criteria:**
- [ ] Bundle size reasonable
- [ ] No significant performance degradation
- [ ] Tour initialization fast (<100ms)

---

### Test 4.2: Memory Leaks
**Steps:**
1. Open DevTools Memory tab
2. Take heap snapshot
3. Start and complete tour 10 times
4. Take second heap snapshot
5. Compare memory usage

**Expected Result:**
- Memory increase < 5MB
- No detached DOM nodes
- Event listeners properly cleaned up

**Pass Criteria:**
- [ ] Memory usage stable
- [ ] No memory leaks detected
- [ ] Tour cleanup works correctly

---

## Bug Testing

### Test 5.1: Edge Cases
**Steps:**
1. Start tour
2. Quickly resize browser during tour
3. Switch tabs during tour
4. Try to interact with page during tour
5. Refresh page mid-tour

**Expected Result:**
- Tour handles interruptions gracefully
- No JavaScript errors
- State remains consistent

**Pass Criteria:**
- [ ] Resize doesn't break tour
- [ ] Tab switching handled correctly
- [ ] Page interaction blocked by overlay
- [ ] Refresh resets tour state

---

### Test 5.2: Missing Elements
**Steps:**
1. Modify DOM to remove tour target element
2. Start tour
3. Navigate to step targeting missing element

**Expected Result:**
- Tour skips step gracefully
- Error logged to console (but doesn't crash)
- User can continue tour

**Pass Criteria:**
- [ ] No JavaScript crashes
- [ ] Tour continues to next step
- [ ] Warning logged to console

---

## Success Criteria Summary

### Critical Tests (Must Pass)
- [ ] Test 1.1: Auto-start works
- [ ] Test 1.2: All steps display correctly
- [ ] Test 1.4: "Don't show again" works
- [ ] Test 2.1: Monitor tour auto-starts
- [ ] Test 2.3: All elements highlighted
- [ ] Test 3.1: Tours don't conflict
- [ ] Test 3.2: Cross-browser compatibility

### Important Tests (Should Pass)
- [ ] Test 1.3: Skip functionality
- [ ] Test 1.5: Manual trigger
- [ ] Test 1.6: Responsive design
- [ ] Test 2.2: Status-specific steps
- [ ] Test 2.4: Live execution interaction
- [ ] Test 3.3: Accessibility

### Nice-to-Have Tests (Good to Pass)
- [ ] Test 4.1: Performance
- [ ] Test 4.2: Memory leaks
- [ ] Test 5.1: Edge cases
- [ ] Test 5.2: Missing elements

---

## Known Issues / Limitations

### Current Limitations:
1. **Auto-start delay**: 1 second delay may feel slow on fast connections
2. **Mobile UX**: Some steps may be cramped on very small screens (<375px)
3. **Alpine.js timing**: If Alpine components mount slowly, tour may start before elements exist

### Future Enhancements:
1. Add tour progress indicator (Step X of Y)
2. Implement tour replay functionality
3. Add analytics to track tour completion rates
4. Create admin panel to customize tour content
5. Support multiple languages (currently English only)

---

## Debugging Commands

### Clear Tour State
```javascript
// Clear crew launch tour
localStorage.removeItem('ainstein_tour_crew_launch_completed');

// Clear execution monitor tour
localStorage.removeItem('ainstein_tour_execution_monitor_completed');

// Clear all tours
localStorage.clear();
```

### Manual Tour Start
```javascript
// Start crew launch tour
window.startCrewLaunchTour();

// Start execution monitor tour
window.startExecutionMonitorTour();
```

### Check Tour State
```javascript
// Check if crew launch tour completed
console.log(localStorage.getItem('ainstein_tour_crew_launch_completed'));

// Check if execution monitor tour completed
console.log(localStorage.getItem('ainstein_tour_execution_monitor_completed'));
```

---

## Reporting Issues

When reporting issues, include:
1. Browser and version
2. Screen size / device
3. Steps to reproduce
4. Console errors (screenshot)
5. Expected vs actual behavior
6. localStorage state

**Example Issue Report:**
```
Browser: Chrome 120.0.6099.130
Device: Desktop (1920x1080)
Steps:
1. Navigate to /dashboard/crews/1
2. Tour auto-starts
3. Click "Next" on step 3
Problem: Execute tab doesn't switch automatically
Console Error: "Cannot read property 'click' of null"
localStorage: empty
```

---

## Contact & Support

For questions or issues with the onboarding tours:
- Check console for JavaScript errors
- Review this testing document
- Test with cleared localStorage
- Verify assets are built (`npm run build`)

**Tip**: Most tour issues are caused by:
1. Assets not rebuilt after code changes
2. Browser cache (hard refresh: Ctrl+Shift+R)
3. localStorage blocking tour auto-start
4. Alpine.js components not fully mounted
