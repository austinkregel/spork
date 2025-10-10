# MCP Server Configuration for Spork

This directory contains the Model Context Protocol (MCP) server configuration for the Spork project.

## What are MCP Servers?

MCP (Model Context Protocol) servers allow AI assistants like Claude to connect to various tools and services to enhance the development experience. These servers provide direct access to:

- **Laravel operations** (migrations, artisan commands, framework features)
- **GitHub operations** (issues, pull requests, repository management)
- **Filesystem operations** (reading and writing files)
- **Database operations** (queries, schema inspection)

## Installation

### Prerequisites

- Node.js and npm installed
- For GitHub server: A GitHub Personal Access Token

### Setup Instructions

1. **Copy the MCP configuration to your Claude Desktop settings:**

   The MCP configuration file (`mcp.json`) needs to be merged into your Claude Desktop configuration file, typically located at:
   - macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`
   - Linux: `~/.config/claude/claude_desktop_config.json`
   - Windows: `%APPDATA%\Claude\claude_desktop_config.json`

2. **Update the configuration paths:**

   Edit the configuration to replace placeholder values:
   
   - Replace `/absolute/path/to/spork` with the actual absolute path to this repository
   - Replace `your_github_token_here` with your GitHub Personal Access Token

3. **Create a GitHub Personal Access Token (for GitHub MCP server):**

   - Go to: https://github.com/settings/tokens
   - Click "Generate new token (classic)"
   - Select the following scopes:
     - `repo` (Full control of private repositories)
     - `read:org` (Read org and team membership)
     - `read:user` (Read user profile data)
   - Copy the generated token and paste it in the configuration

## MCP Servers Included

### Laravel MCP Server

Provides Laravel-specific operations and framework features.

**Configuration:**
```json
{
  "laravel": {
    "command": "npx",
    "args": ["-y", "@modelcontextprotocol/server-laravel"],
    "env": {
      "PROJECT_PATH": "/absolute/path/to/spork"
    }
  }
}
```

### GitHub MCP Server

Enables repository operations, issues, and pull request management.

**Configuration:**
```json
{
  "github": {
    "command": "npx",
    "args": ["-y", "@modelcontextprotocol/server-github"],
    "env": {
      "GITHUB_PERSONAL_ACCESS_TOKEN": "your_github_token_here"
    }
  }
}
```

### Filesystem MCP Server

Allows file operations and navigation within the repository.

**Configuration:**
```json
{
  "filesystem": {
    "command": "npx",
    "args": [
      "-y",
      "@modelcontextprotocol/server-filesystem",
      "/absolute/path/to/spork"
    ]
  }
}
```

### SQLite MCP Server

Provides database query and schema inspection capabilities.

**Configuration:**
```json
{
  "sqlite": {
    "command": "npx",
    "args": [
      "-y",
      "@modelcontextprotocol/server-sqlite",
      "/absolute/path/to/spork/database/database.sqlite"
    ]
  }
}
```

## Alternative Database Configurations

If you're using MySQL or PostgreSQL instead of SQLite, replace the SQLite server configuration with one of the following:

### MySQL

```json
{
  "mysql": {
    "command": "npx",
    "args": ["-y", "@modelcontextprotocol/server-mysql"],
    "env": {
      "MYSQL_HOST": "localhost",
      "MYSQL_PORT": "3306",
      "MYSQL_USER": "your_user",
      "MYSQL_PASSWORD": "your_password",
      "MYSQL_DATABASE": "spork"
    }
  }
}
```

### PostgreSQL

```json
{
  "postgres": {
    "command": "npx",
    "args": ["-y", "@modelcontextprotocol/server-postgres"],
    "env": {
      "POSTGRES_CONNECTION_STRING": "postgresql://user:password@localhost:5432/spork"
    }
  }
}
```

## Verifying Installation

After adding the configuration to your Claude Desktop settings:

1. Restart Claude Desktop
2. Open a new conversation
3. The MCP servers should automatically connect
4. You can verify by asking Claude to use these tools for Laravel, GitHub, or filesystem operations

## Troubleshooting

- **Servers not connecting:** Ensure the paths in the configuration are absolute paths, not relative
- **GitHub server failing:** Verify your Personal Access Token has the correct scopes and hasn't expired
- **Database server failing:** Check that the database file exists and is accessible
- **Permission errors:** Ensure Claude Desktop has permission to execute `npx` commands

## Security Notes

- **Never commit your actual GitHub Personal Access Token to version control**
- The `mcp.json` file in this repository contains placeholder values only
- Keep your actual configuration file with real tokens private
- Consider using environment variables or a separate secrets management system for sensitive values

## Resources

- [Model Context Protocol Documentation](https://modelcontextprotocol.io/)
- [Claude Desktop Documentation](https://claude.ai/docs)
- [GitHub Token Documentation](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)
