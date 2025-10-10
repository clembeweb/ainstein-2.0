#!/usr/bin/env python3
"""
Simple test to verify CrewAI installation and basic functionality
"""

import sys
from dotenv import load_dotenv
from crewai import Agent, Task, Crew

# Load .env from parent directory
load_dotenv('../.env')

def test_installation():
    """Test that all required packages are installed"""
    print("Testing CrewAI installation...\n")

    try:
        import crewai
        print(f"[OK] CrewAI version: {crewai.__version__}")

        import pymysql
        print(f"[OK] PyMySQL installed")

        print("\n[OK] All packages installed successfully!")
        return True

    except ImportError as e:
        print(f"\n[ERROR] Installation error: {e}")
        return False


def test_simple_agent():
    """Test creating a simple agent"""
    print("\nTesting agent creation...\n")

    try:
        agent = Agent(
            role='Test Agent',
            goal='Verify CrewAI functionality',
            backstory='A simple test agent',
            verbose=False
        )

        print(f"[OK] Agent created: {agent.role}")
        return True

    except Exception as e:
        print(f"[ERROR] Agent creation failed: {e}")
        return False


def test_simple_task():
    """Test creating a simple task"""
    print("\nTesting task creation...\n")

    try:
        agent = Agent(
            role='Assistant',
            goal='Help with tasks',
            backstory='A helpful assistant',
            verbose=False
        )

        task = Task(
            description='Say hello',
            agent=agent,
            expected_output='A greeting message'
        )

        print(f"[OK] Task created: {task.description}")
        return True

    except Exception as e:
        print(f"[ERROR] Task creation failed: {e}")
        return False


def main():
    """Run all tests"""
    print("=" * 60)
    print("CREWAI BASIC TESTS")
    print("=" * 60)

    tests = [
        ("Installation", test_installation),
        ("Agent Creation", test_simple_agent),
        ("Task Creation", test_simple_task),
    ]

    results = []
    for name, test_func in tests:
        result = test_func()
        results.append((name, result))

    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)

    passed = sum(1 for _, result in results if result)
    total = len(results)

    for name, result in results:
        status = "[PASS]" if result else "[FAIL]"
        print(f"{status}: {name}")

    print(f"\nTotal: {passed}/{total} tests passed")
    print("=" * 60)

    return 0 if passed == total else 1


if __name__ == '__main__':
    sys.exit(main())
