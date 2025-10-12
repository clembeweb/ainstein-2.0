# 🧪 Testing Documentation - AINSTEIN

**Last Updated**: 2025-10-10
**Status**: ✅ Organized and Updated

---

## 📋 Testing Documentation Index

### Core Testing Reports
- [**Complete Testing Report**](COMPLETE_TESTING_REPORT.md) - Comprehensive end-to-end testing checklist
- [**Manual Browser Test Guide**](MANUAL_BROWSER_TEST_GUIDE.md) - Step-by-step browser testing procedures
- [**Tour Test Report**](TOUR_TEST_REPORT.md) - CrewAI onboarding tour testing results
- [**Quick Reference**](QUICK_REFERENCE.md) - Quick commands and testing shortcuts

### Automated Testing
- **Location**: `tests/Feature/` and `tests/Unit/`
- **OAuth Tests**: `tests/Feature/OAuthMultiTenantTest.php`
- **CrewAI Tests**: `tests/Feature/CrewAI/`
- **Run Tests**: `php artisan test`

### Testing Environments

#### Local Testing
```bash
php artisan test
php artisan test --filter=OAuth
php artisan test --coverage
```

#### Production Testing
- **URL**: https://ainstein.it
- **Test Credentials**: See [CREDENTIALS-FOR-TESTING.md](../../CREDENTIALS-FOR-TESTING.md)

### Testing Categories

#### 1. Authentication & Security
- Login/Logout flows
- OAuth Social Login (Google, Facebook)
- Multi-tenant isolation
- API token authentication
- Permission policies

#### 2. Feature Testing
- **Campaign Generator**: RSA and PMAX campaign creation
- **Content Generator**: Page, prompt, and generation management
- **CrewAI System**: Multi-agent orchestration
- **API Keys**: Generation and management
- **Admin Panel**: Super admin functions

#### 3. Integration Testing
- OpenAI API integration
- OAuth provider integration
- Database relationships
- Queue job processing

#### 4. UI/UX Testing
- Responsive design
- Form validation
- Navigation flow
- Onboarding tours
- Error handling

### Testing Priorities

#### 🔴 Critical (Must Test)
1. User authentication
2. Tenant isolation
3. OAuth login flow
4. Campaign generation with AI
5. Token usage tracking

#### 🟡 High Priority
1. Content generation workflow
2. API key management
3. CrewAI execution
4. Admin panel access
5. Form validations

#### 🟢 Standard Priority
1. UI responsiveness
2. Export functions
3. Search and filters
4. Pagination
5. Toast notifications

### Test Coverage Status

| Component | Unit Tests | Feature Tests | E2E Tests | Coverage |
|-----------|------------|---------------|-----------|----------|
| Auth/OAuth | ✅ | ✅ | ✅ | 95% |
| Campaign Generator | ✅ | ✅ | ⚠️ | 85% |
| Content Generator | ✅ | ✅ | ⚠️ | 80% |
| CrewAI System | ✅ | ⚠️ | ⚠️ | 70% |
| Admin Panel | ⚠️ | ⚠️ | ⚠️ | 60% |
| API Endpoints | ✅ | ✅ | ⚠️ | 75% |

### Common Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
php artisan test --coverage --min=80

# Run specific test file
php artisan test tests/Feature/OAuthMultiTenantTest.php

# Run tests in parallel
php artisan test --parallel

# Debug test
php artisan test --debug

# Stop on failure
php artisan test --stop-on-failure
```

### Browser Testing Checklist

#### Quick Smoke Test (5 min)
- [ ] Login works
- [ ] Dashboard loads
- [ ] Create campaign
- [ ] View campaigns list
- [ ] Logout works

#### Full Test Suite (30 min)
- [ ] Complete authentication flow
- [ ] Test all CRUD operations
- [ ] Verify tenant isolation
- [ ] Test OAuth login
- [ ] Check responsive design
- [ ] Validate all forms
- [ ] Test error scenarios

### Known Issues & Workarounds

| Issue | Impact | Workaround | Status |
|-------|--------|------------|--------|
| OAuth redirect on localhost | Low | Use production URL | Document |
| Queue jobs in testing | Medium | Use sync driver | Resolved |
| Seeder data conflicts | Low | Fresh migrate | Resolved |

### Testing Tools & Resources

- **PHPUnit**: Core testing framework
- **Laravel Dusk**: Browser automation (if needed)
- **Pest PHP**: Modern testing framework (optional)
- **Mockery**: Mocking library
- **Faker**: Test data generation

### Continuous Integration

```yaml
# Example GitHub Actions workflow
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

### Testing Best Practices

1. **Isolation**: Each test should be independent
2. **Repeatability**: Tests should produce same results
3. **Speed**: Keep tests fast (< 10 seconds total)
4. **Coverage**: Aim for >80% code coverage
5. **Documentation**: Document complex test scenarios
6. **Maintenance**: Update tests with code changes

### Support & Troubleshooting

For testing issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review test output carefully
3. Run tests individually to isolate issues
4. Check database state between tests
5. Verify environment configuration

---

**Related Documentation**:
- [Development Guide](../DEVELOPMENT.md)
- [API Documentation](../api/README.md)
- [Deployment Guide](../DEPLOYMENT.md)