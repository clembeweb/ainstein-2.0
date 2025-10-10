#!/usr/bin/env python3
"""
CrewAI Proof of Concept for AINSTEIN Platform
Tests basic CrewAI functionality and Laravel integration
"""

import os
import sys
import json
from datetime import datetime
from dotenv import load_dotenv
from crewai import Agent, Task, Crew, Process
from langchain_openai import ChatOpenAI

# Load environment variables from Laravel .env
load_dotenv('../.env')

class CrewPOC:
    """Proof of Concept for CrewAI integration"""

    def __init__(self):
        """Initialize POC with OpenAI configuration"""
        self.api_key = os.getenv('OPENAI_API_KEY')
        if not self.api_key:
            raise ValueError("OPENAI_API_KEY not found in .env file")

        # Initialize LLM
        self.llm = ChatOpenAI(
            model=os.getenv('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            temperature=0.7,
            api_key=self.api_key
        )

        self.execution_log = []
        self.total_tokens = 0

    def log(self, level, message, data=None):
        """Log execution steps"""
        log_entry = {
            'timestamp': datetime.now().isoformat(),
            'level': level,
            'message': message,
            'data': data
        }
        self.execution_log.append(log_entry)
        print(f"[{level.upper()}] {message}")
        if data:
            print(f"  Data: {json.dumps(data, indent=2)}")

    def create_simple_crew(self):
        """Create a simple test crew"""
        self.log('info', 'Creating test crew with 2 agents')

        # Agent 1: Content Researcher
        researcher = Agent(
            role='Content Researcher',
            goal='Find accurate and relevant information about AI trends',
            backstory="""You are an expert researcher specializing in artificial intelligence
            and technology trends. You excel at finding credible sources and synthesizing
            information into clear, actionable insights.""",
            verbose=True,
            allow_delegation=False,
            llm=self.llm
        )

        # Agent 2: Content Writer
        writer = Agent(
            role='Content Writer',
            goal='Create engaging and informative content based on research',
            backstory="""You are a skilled technical writer with expertise in making
            complex AI topics accessible to a general audience. You write clear,
            engaging content that educates and informs.""",
            verbose=True,
            allow_delegation=False,
            llm=self.llm
        )

        self.log('info', 'Agents created successfully', {
            'researcher': researcher.role,
            'writer': writer.role
        })

        return researcher, writer

    def create_tasks(self, researcher, writer, topic='AI trends 2025'):
        """Create tasks for the crew"""
        self.log('info', f'Creating tasks for topic: {topic}')

        # Task 1: Research
        research_task = Task(
            description=f"""Research the top 3 {topic}.
            Focus on practical applications and real-world impact.
            Provide sources and brief explanations for each trend.""",
            agent=researcher,
            expected_output="A list of 3 AI trends with descriptions and sources"
        )

        # Task 2: Write
        writing_task = Task(
            description="""Using the research findings, write a brief article (200 words)
            about these AI trends. Make it engaging and accessible to a general audience.
            Include a compelling introduction and conclusion.""",
            agent=writer,
            expected_output="A 200-word article about AI trends"
        )

        self.log('info', 'Tasks created successfully', {
            'task_count': 2
        })

        return [research_task, writing_task]

    def execute_crew(self, topic='AI trends 2025'):
        """Execute a complete crew"""
        try:
            self.log('info', 'Starting crew execution', {'topic': topic})

            # Create agents
            researcher, writer = self.create_simple_crew()

            # Create tasks
            tasks = self.create_tasks(researcher, writer, topic)

            # Create crew
            crew = Crew(
                agents=[researcher, writer],
                tasks=tasks,
                process=Process.sequential,
                verbose=2
            )

            self.log('info', 'Crew configured, starting execution')

            # Execute crew
            result = crew.kickoff()

            self.log('info', 'Crew execution completed successfully')

            # Extract token usage (if available)
            # Note: CrewAI doesn't always provide token counts directly
            # We'll estimate based on output length
            result_text = str(result)
            estimated_tokens = len(result_text.split()) * 1.3  # Rough estimate

            self.total_tokens = int(estimated_tokens)

            return {
                'success': True,
                'result': result_text,
                'tokens_used': self.total_tokens,
                'execution_log': self.execution_log,
                'cost': self.estimate_cost(self.total_tokens)
            }

        except Exception as e:
            self.log('error', f'Crew execution failed: {str(e)}')
            return {
                'success': False,
                'error': str(e),
                'execution_log': self.execution_log
            }

    def estimate_cost(self, tokens):
        """Estimate cost based on tokens (gpt-4o-mini pricing)"""
        # gpt-4o-mini: $0.00015 per 1K input tokens, $0.0006 per 1K output tokens
        # Rough average: $0.000375 per 1K tokens
        return round((tokens / 1000) * 0.000375, 4)

    def save_results(self, results, filename='poc_results.json'):
        """Save execution results to JSON file"""
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        self.log('info', f'Results saved to {filename}')


def main():
    """Main POC execution"""
    print("=" * 60)
    print("CREWAI PROOF OF CONCEPT - AINSTEIN PLATFORM")
    print("=" * 60)
    print()

    try:
        # Initialize POC
        poc = CrewPOC()

        # Execute crew
        topic = sys.argv[1] if len(sys.argv) > 1 else 'AI trends 2025'
        results = poc.execute_crew(topic)

        # Save results
        poc.save_results(results)

        # Print summary
        print("\n" + "=" * 60)
        print("EXECUTION SUMMARY")
        print("=" * 60)
        print(f"Success: {results['success']}")

        if results['success']:
            print(f"Tokens Used: {results['tokens_used']}")
            print(f"Estimated Cost: ${results['cost']}")
            print(f"\nResult Preview:")
            print("-" * 60)
            print(results['result'][:500] + "..." if len(results['result']) > 500 else results['result'])
        else:
            print(f"Error: {results.get('error', 'Unknown error')}")

        print("\n" + "=" * 60)

        return 0 if results['success'] else 1

    except Exception as e:
        print(f"\n[CRITICAL ERROR] {str(e)}")
        import traceback
        traceback.print_exc()
        return 1


if __name__ == '__main__':
    sys.exit(main())
