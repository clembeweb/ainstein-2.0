<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CrewAgentTool;
use Illuminate\Support\Str;

class CrewAgentToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tools = [
            // Web Search & Scraping Tools
            [
                'name' => 'SerperDevTool',
                'description' => 'Performs web searches using Serper.dev API. Returns relevant search results with snippets, links, and metadata.',
                'type' => 'builtin',
                'configuration' => [
                    'api_required' => true,
                    'api_key_env' => 'SERPER_API_KEY',
                    'rate_limit' => 100,
                    'parameters' => [
                        'query' => 'Search query string',
                        'num_results' => 'Number of results to return (default: 10)'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'WebsiteSearchTool',
                'description' => 'Searches content within a specific website. Useful for finding information on company sites, documentation, or blogs.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'url' => 'Website URL to search',
                        'query' => 'Search query'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'ScrapeWebsiteTool',
                'description' => 'Extracts content from web pages. Can scrape text, images, links, and structured data.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'url' => 'URL to scrape',
                        'selector' => 'CSS selector (optional)',
                        'extract_links' => 'Extract all links (boolean)'
                    ]
                ],
                'is_active' => true
            ],

            // File & Directory Tools
            [
                'name' => 'FileReadTool',
                'description' => 'Reads content from files. Supports text files, JSON, CSV, and more.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'file_path' => 'Path to file',
                        'encoding' => 'File encoding (default: utf-8)'
                    ],
                    'allowed_extensions' => ['txt', 'md', 'json', 'csv', 'xml']
                ],
                'is_active' => true
            ],
            [
                'name' => 'DirectoryReadTool',
                'description' => 'Lists files and directories in a specified path. Useful for file exploration.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'directory_path' => 'Path to directory',
                        'recursive' => 'Include subdirectories (boolean)'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'FileWriteTool',
                'description' => 'Writes content to files. Can create new files or overwrite existing ones.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'file_path' => 'Path to file',
                        'content' => 'Content to write',
                        'mode' => 'Write mode (w, a, etc.)'
                    ],
                    'permissions_required' => true
                ],
                'is_active' => true
            ],

            // Documentation Tools
            [
                'name' => 'MDXSearchTool',
                'description' => 'Searches through MDX documentation files. Perfect for searching API docs or technical documentation.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'mdx_path' => 'Path to MDX files',
                        'query' => 'Search query'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'PDFSearchTool',
                'description' => 'Searches and extracts text from PDF documents.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'pdf_path' => 'Path to PDF file',
                        'query' => 'Search query',
                        'page_range' => 'Specific pages to search (optional)'
                    ]
                ],
                'is_active' => true
            ],

            // Code & Development Tools
            [
                'name' => 'CodeInterpreterTool',
                'description' => 'Executes Python code in a sandboxed environment. Useful for calculations, data processing, and analysis.',
                'type' => 'builtin',
                'configuration' => [
                    'security' => 'sandboxed',
                    'timeout' => 30,
                    'parameters' => [
                        'code' => 'Python code to execute'
                    ],
                    'permissions_required' => true
                ],
                'is_active' => false // Disabled by default for security
            ],
            [
                'name' => 'GithubSearchTool',
                'description' => 'Searches GitHub repositories, issues, and code. Requires GitHub API token.',
                'type' => 'builtin',
                'configuration' => [
                    'api_required' => true,
                    'api_key_env' => 'GITHUB_TOKEN',
                    'parameters' => [
                        'query' => 'Search query',
                        'type' => 'Search type (repositories, code, issues)'
                    ]
                ],
                'is_active' => true
            ],

            // Data & API Tools
            [
                'name' => 'JSONSearchTool',
                'description' => 'Searches and extracts data from JSON files or API responses.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'json_path' => 'Path to JSON file or URL',
                        'json_path_query' => 'JSONPath query'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'CSVSearchTool',
                'description' => 'Searches through CSV files and extracts data based on criteria.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'csv_path' => 'Path to CSV file',
                        'query' => 'Search query',
                        'columns' => 'Specific columns to search'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'XMLSearchTool',
                'description' => 'Parses and searches XML documents using XPath queries.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'xml_path' => 'Path to XML file',
                        'xpath_query' => 'XPath query string'
                    ]
                ],
                'is_active' => true
            ],

            // Custom API Tool
            [
                'name' => 'CustomAPITool',
                'description' => 'Makes HTTP requests to custom APIs. Fully configurable for any REST API endpoint.',
                'type' => 'custom',
                'configuration' => [
                    'parameters' => [
                        'url' => 'API endpoint URL',
                        'method' => 'HTTP method (GET, POST, etc.)',
                        'headers' => 'Request headers (JSON)',
                        'body' => 'Request body (JSON)'
                    ],
                    'rate_limit' => 60
                ],
                'is_active' => true
            ],

            // YouTube Tool
            [
                'name' => 'YoutubeChannelSearchTool',
                'description' => 'Searches YouTube channels and retrieves video information.',
                'type' => 'builtin',
                'configuration' => [
                    'api_required' => true,
                    'api_key_env' => 'YOUTUBE_API_KEY',
                    'parameters' => [
                        'channel_id' => 'YouTube channel ID',
                        'query' => 'Search query within channel'
                    ]
                ],
                'is_active' => true
            ],
            [
                'name' => 'YoutubeVideoSearchTool',
                'description' => 'Searches YouTube videos and retrieves transcripts, metadata, and comments.',
                'type' => 'builtin',
                'configuration' => [
                    'parameters' => [
                        'query' => 'Search query',
                        'max_results' => 'Maximum results to return'
                    ]
                ],
                'is_active' => true
            ],

            // Browser Automation
            [
                'name' => 'BrowserbaseTool',
                'description' => 'Controls a headless browser for web automation. Can interact with dynamic websites.',
                'type' => 'builtin',
                'configuration' => [
                    'api_required' => true,
                    'api_key_env' => 'BROWSERBASE_API_KEY',
                    'parameters' => [
                        'url' => 'URL to visit',
                        'actions' => 'Browser actions to perform'
                    ],
                    'permissions_required' => true
                ],
                'is_active' => false // Disabled by default
            ],
        ];

        foreach ($tools as $toolData) {
            CrewAgentTool::create([
                'id' => Str::ulid(),
                'name' => $toolData['name'],
                'description' => $toolData['description'],
                'type' => $toolData['type'],
                'configuration' => $toolData['configuration'],
                'is_active' => $toolData['is_active'],
            ]);
        }

        $this->command->info('✓ ' . count($tools) . ' CrewAI tools seeded successfully!');
    }
}
