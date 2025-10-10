# CrewAI Integration for AINSTEIN

Python microservice for executing CrewAI crews from Laravel.

## Prerequisites

- Python 3.10 or higher
- pip (Python package manager)
- OpenAI API key configured in Laravel `.env`

## Installation

### 1. Create Virtual Environment

```bash
# Windows
cd C:\laragon\www\ainstein-3\python
python -m venv venv
venv\Scripts\activate

# Linux/Mac
cd /path/to/ainstein-3/python
python3 -m venv venv
source venv/bin/activate
```

### 2. Install Dependencies

```bash
pip install -r requirements.txt
```

## Testing

### Basic Installation Test

Verify all packages are installed correctly:

```bash
python test_crew.py
```

Expected output:
```
✓ All packages installed successfully!
✓ Agent created: Test Agent
✓ Task created: Say hello
Total: 3/3 tests passed
```

### Proof of Concept Test

Run a complete crew execution:

```bash
python crew_poc.py
```

Or with a custom topic:

```bash
python crew_poc.py "Machine Learning trends"
```

This will:
1. Create a research agent and writer agent
2. Execute a sequential crew workflow
3. Generate content about the topic
4. Save results to `poc_results.json`
5. Track token usage and costs

## Output

The POC saves results in JSON format:

```json
{
  "success": true,
  "result": "Generated article text...",
  "tokens_used": 1500,
  "cost": 0.0005,
  "execution_log": [
    {
      "timestamp": "2025-10-10T10:30:00",
      "level": "info",
      "message": "Starting crew execution",
      "data": {"topic": "AI trends 2025"}
    }
  ]
}
```

## Integration with Laravel

### Communication Flow

```
Laravel Queue Job → Python Script → CrewAI Execution → JSON Output → Laravel Database
```

### Execution from Laravel

```php
use Symfony\Component\Process\Process;

$process = new Process([
    'python',
    base_path('python/crew_poc.py'),
    $topic
]);

$process->setTimeout(600); // 10 minutes
$process->run();

$results = json_decode(file_get_contents('python/poc_results.json'), true);
```

## Configuration

### Environment Variables

The script reads from Laravel's `.env` file:

- `OPENAI_API_KEY` - Required for OpenAI API access
- `OPENAI_DEFAULT_MODEL` - Defaults to `gpt-4o-mini`

### Model Configuration

Edit `crew_poc.py` to change LLM settings:

```python
self.llm = ChatOpenAI(
    model='gpt-4o',  # or 'gpt-4o-mini', 'gpt-4'
    temperature=0.7,
    api_key=self.api_key
)
```

## Cost Tracking

Token usage is tracked and costs are estimated based on:
- **gpt-4o-mini**: ~$0.000375 per 1K tokens (average)
- **gpt-4o**: ~$0.0025 per 1K tokens (average)

Actual costs may vary based on input/output token ratio.

## Troubleshooting

### ImportError: No module named 'crewai'

```bash
# Make sure virtual environment is activated
venv\Scripts\activate  # Windows
source venv/bin/activate  # Linux/Mac

# Reinstall dependencies
pip install -r requirements.txt
```

### OpenAI API Key Not Found

Verify `.env` file in Laravel root contains:
```
OPENAI_API_KEY=sk-...
```

### Permission Denied (Linux/Mac)

```bash
chmod +x crew_poc.py test_crew.py
```

## Next Steps

1. ✅ Run `test_crew.py` to verify installation
2. ✅ Run `crew_poc.py` to test execution
3. 🔄 Integrate with Laravel Queue Jobs
4. 🔄 Implement database communication
5. 🔄 Add real-time logging
6. 🔄 Deploy to production environment

## Directory Structure

```
python/
├── venv/                  # Virtual environment (gitignored)
├── crew_poc.py           # Main POC script
├── test_crew.py          # Installation test
├── requirements.txt      # Python dependencies
├── README.md            # This file
└── poc_results.json     # Output file (generated)
```

## Support

For issues or questions, check:
- [CrewAI Documentation](https://docs.crewai.com)
- [LangChain Documentation](https://python.langchain.com)
- AINSTEIN Project Documentation
