# Quick Manual Browser Testing Guide
**CrewAI Tours - 15 Minute Quick Test**

---

## Quick Start (5 minutes)

### 1. Setup
```bash
# Open browser (Chrome/Firefox recommended)
# Navigate to: http://127.0.0.1:8000/login
# Login: admin@ainstein.com / password123
```

### 2. Clear LocalStorage (for fresh test)
```javascript
// In browser console (F12):
localStorage.clear()
```

### 3. Test Crew Tour
```
Visit: http://127.0.0.1:8000/dashboard/crews/01k775heeb9hwm8ahg0tnf8c9m
Wait 1 second → Tour should auto-start ✅
Click through all 8 steps → Verify Step 4 switches to Execute tab ✅
Check "Don't show again" on last step → Click Finish ✅
Refresh page → Tour should NOT start again ✅
```

### 4. Test Manual Trigger
```
Click "Show Tour" button (top-right) → Tour starts ✅
Press ESC or click "Skip Tour" → Tour closes ✅
```

### 5. Test Execution Tour
```
Click "Launch Execution" (use Mock mode)
Wait for execution detail page
Tour should auto-start ✅
Navigate through all 9 steps
Complete tour with "Don't show again" ✅
```

---

## Critical Checks (2 minutes)

### Visual Quality
- [ ] Tour popover displays without overlap
- [ ] Arrow indicators point correctly
- [ ] Text is readable
- [ ] Buttons are clickable
- [ ] Backdrop overlay visible

### Functionality
- [ ] Next/Previous buttons work
- [ ] Keyboard arrows work (LEFT/RIGHT)
- [ ] ESC key closes tour
- [ ] Tab auto-switch works (crew tour step 4)
- [ ] LocalStorage persistence works

### Console
- [ ] Open DevTools (F12) → Console tab
- [ ] No red errors should appear
- [ ] No 404 errors for assets

---

## Expected Behavior Summary

### Crew Tour
- **Steps:** 8 total
- **Auto-start:** After 1 second on first visit
- **Special Feature:** Step 4 auto-switches to Execute tab
- **LocalStorage Key:** `ainstein_tour_crew_launch_completed`

### Execution Tour
- **Steps:** 9 total
- **Auto-start:** On first execution view
- **Adapts to:** Execution status (running/completed/failed)
- **LocalStorage Key:** `ainstein_tour_execution_monitor_completed`

---

## Troubleshooting

### Tour Doesn't Start
```javascript
// Check localStorage:
console.log(localStorage.getItem('ainstein_tour_crew_launch_completed'))
// If 'true', clear it:
localStorage.removeItem('ainstein_tour_crew_launch_completed')
// Refresh page
```

### JavaScript Errors
```javascript
// Check if functions exist:
console.log(typeof window.startCrewLaunchTour)        // Should be 'function'
console.log(typeof window.startExecutionMonitorTour)  // Should be 'function'
console.log(typeof window.Shepherd)                   // Should be 'function'
```

### Tour Doesn't Display Correctly
- Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Clear browser cache
- Check console for CSS loading errors

---

## Quick Test Script

**Copy/paste this into browser console to verify setup:**

```javascript
// Verify all tour functions loaded
console.log('🔍 Checking tour setup...');

const checks = [
    { name: 'Crew Launch Tour', test: () => typeof window.startCrewLaunchTour === 'function' },
    { name: 'Execution Monitor Tour', test: () => typeof window.startExecutionMonitorTour === 'function' },
    { name: 'Shepherd.js Library', test: () => typeof window.Shepherd === 'function' },
    { name: 'Alpine.js', test: () => typeof window.Alpine !== 'undefined' },
];

checks.forEach(check => {
    const result = check.test();
    console.log(`${result ? '✅' : '❌'} ${check.name}: ${result ? 'READY' : 'MISSING'}`);
});

console.log('\n📊 LocalStorage Status:');
console.log('Crew Tour Completed:', localStorage.getItem('ainstein_tour_crew_launch_completed') || 'Not yet');
console.log('Execution Tour Completed:', localStorage.getItem('ainstein_tour_execution_monitor_completed') || 'Not yet');

console.log('\n🎯 All checks done! See results above.');
```

---

## Success Indicators

✅ **Everything Working:**
- Tour starts automatically on first visit
- Navigation buttons respond
- Tab auto-switch works (crew tour)
- "Don't show again" saves preference
- Manual trigger works
- No console errors
- Visual elements render correctly

❌ **Issues to Report:**
- JavaScript errors in console
- Tour doesn't start
- Visual glitches or overlap
- Buttons not clickable
- Tab switch doesn't work
- LocalStorage not persisting

---

## Full Testing (30 minutes)

For comprehensive testing, follow the complete checklist in:
`/tests/TOUR_TEST_REPORT.md` (Section: Manual Browser Testing Checklist)

---

**Quick Test Time:** ~15 minutes
**Full Test Time:** ~30 minutes
**Recommended Browser:** Chrome or Firefox (latest version)
