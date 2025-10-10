#!/usr/bin/env python3
"""
Laravel-Python Bridge for CrewAI Execution
Handles communication between Laravel and Python CrewAI workers
"""

import sys
import json
import os
from datetime import datetime
from dotenv import load_dotenv
from crewai import Agent, Task, Crew, Process
from langchain_openai import ChatOpenAI

# Load Laravel .env
load_dotenv('../.env')


class LaravelBridge:
    """Bridge between Laravel and CrewAI"""

    def __init__(self, execution_id):
        """Initialize bridge with execution ID from Laravel"""
        self.execution_id = execution_id
        self.api_key = os.getenv('OPENAI_API_KEY')

        if not self.api_key:
            raise ValueError("OPENAI_API_KEY not configured")

        self.llm = ChatOpenAI(
            model=os.getenv('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            temperature=0.7,
            api_key=self.api_key
        )

        self.logs = []
        self.tokens_used = 0

    def log_to_laravel(self, level, message, data=None):
        """Log message that will be sent back to Laravel"""
        log_entry = {
            'execution_id': self.execution_id,
            'level': level,
            'message': message,
            'data': data,
            'timestamp': datetime.now().isoformat()
        }
        self.logs.append(log_entry)

        # Also print for immediate feedback
        print(json.dumps(log_entry))
        sys.stdout.flush()

    def load_crew_config(self, config_json):
        """Load crew configuration from Laravel JSON"""
        try:
            config = json.loads(config_json)
            return config
        except json.JSONDecodeError as e:
            self.log_to_laravel('error', f'Invalid JSON configuration: {e}')
            raise

    def build_agents(self, agents_config):
        """Build CrewAI agents from configuration"""
        agents = []

        for agent_config in agents_config:
            self.log_to_laravel('info', f"Creating agent: {agent_config['name']}")

            agent = Agent(
                role=agent_config['role'],
                goal=agent_config.get('goal', ''),
                backstory=agent_config.get('backstory', ''),
                verbose=True,
                allow_delegation=agent_config.get('allow_delegation', False),
                llm=self.llm
            )

            agents.append({
                'id': agent_config['id'],
                'agent': agent
            })

        return agents

    def build_tasks(self, tasks_config, agents):
        """Build CrewAI tasks from configuration"""
        tasks = []

        # Create agent lookup
        agent_lookup = {a['id']: a['agent'] for a in agents}

        for task_config in tasks_config:
            self.log_to_laravel('info', f"Creating task: {task_config['description'][:50]}...")

            # Find corresponding agent
            agent = agent_lookup.get(task_config['agent_id'])
            if not agent:
                self.log_to_laravel('warning', f"Agent not found for task, using first agent")
                agent = agents[0]['agent']

            task = Task(
                description=task_config['description'],
                agent=agent,
                expected_output=task_config.get('expected_output', ''),
                context=task_config.get('context')
            )

            tasks.append(task)

        return tasks

    def execute_crew(self, crew_config, input_variables):
        """Execute a crew based on configuration"""
        try:
            self.log_to_laravel('info', 'Starting crew execution', {
                'crew_id': crew_config['id'],
                'process_type': crew_config['process_type']
            })

            # Build agents
            agents = self.build_agents(crew_config['agents'])

            # Build tasks
            tasks = self.build_tasks(crew_config['tasks'], agents)

            # Create crew
            process = Process.sequential if crew_config['process_type'] == 'sequential' else Process.hierarchical

            crew = Crew(
                agents=[a['agent'] for a in agents],
                tasks=tasks,
                process=process,
                verbose=2
            )

            self.log_to_laravel('info', 'Crew configured, executing tasks')

            # Execute
            result = crew.kickoff()

            # Estimate tokens
            result_text = str(result)
            self.tokens_used = int(len(result_text.split()) * 1.3)

            self.log_to_laravel('info', 'Execution completed', {
                'tokens_used': self.tokens_used,
                'output_length': len(result_text)
            })

            return {
                'success': True,
                'result': result_text,
                'tokens_used': self.tokens_used,
                'cost': self.estimate_cost(self.tokens_used),
                'logs': self.logs
            }

        except Exception as e:
            self.log_to_laravel('error', f'Execution failed: {str(e)}')
            return {
                'success': False,
                'error': str(e),
                'logs': self.logs
            }

    def estimate_cost(self, tokens):
        """Estimate cost based on tokens"""
        # gpt-4o-mini pricing
        return round((tokens / 1000) * 0.000375, 4)


def main():
    """Main entry point for bridge execution"""
    if len(sys.argv) < 3:
        print(json.dumps({
            'success': False,
            'error': 'Usage: python bridge.py <execution_id> <crew_config_json>'
        }))
        return 1

    execution_id = sys.argv[1]
    crew_config_json = sys.argv[2]
    input_variables_json = sys.argv[3] if len(sys.argv) > 3 else '{}'

    try:
        # Initialize bridge
        bridge = LaravelBridge(execution_id)

        # Load configuration
        crew_config = bridge.load_crew_config(crew_config_json)
        input_variables = json.loads(input_variables_json)

        # Execute crew
        result = bridge.execute_crew(crew_config, input_variables)

        # Output final result as JSON
        print("\n__FINAL_RESULT__")
        print(json.dumps(result, ensure_ascii=False, indent=2))

        return 0 if result['success'] else 1

    except Exception as e:
        error_result = {
            'success': False,
            'error': str(e),
            'logs': []
        }
        print("\n__FINAL_RESULT__")
        print(json.dumps(error_result))
        return 1


if __name__ == '__main__':
    sys.exit(main())
