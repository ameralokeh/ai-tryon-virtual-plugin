#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/sdk/server/index.js');
const { StdioServerTransport } = require('@modelcontextprotocol/sdk/server/stdio.js');
const { CallToolRequestSchema, ListToolsRequestSchema } = require('@modelcontextprotocol/sdk/types.js');
const WooCommerceRestApi = require('@woocommerce/woocommerce-rest-api').default;
const fs = require('fs');
const path = require('path');

class DynamicWooCommerceMCPServer {
  constructor() {
    this.server = new Server(
      {
        name: 'dynamic-woocommerce-mcp-server',
        version: '0.1.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.environments = this.loadEnvironments();
    this.currentEnvironment = process.env.WC_ENVIRONMENT || 'production';
    this.setupHandlers();
  }

  loadEnvironments() {
    try {
      const configPath = path.join(__dirname, 'woocommerce-environments.json');
      const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
      return config.environments;
    } catch (error) {
      console.error('Failed to load environments config:', error);
      return {};
    }
  }

  getWooCommerceApi(environment = null) {
    const env = environment || this.currentEnvironment;
    const config = this.environments[env];
    
    if (!config) {
      throw new Error(`Environment '${env}' not found`);
    }

    return new WooCommerceRestApi({
      url: config.url,
      consumerKey: config.consumer_key,
      consumerSecret: config.consumer_secret,
      version: 'wc/v3'
    });
  }

  setupHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return {
        tools: [
          {
            name: 'switch_environment',
            description: 'Switch between different WooCommerce environments',
            inputSchema: {
              type: 'object',
              properties: {
                environment: {
                  type: 'string',
                  enum: Object.keys(this.environments),
                  description: 'Environment to switch to'
                }
              },
              required: ['environment']
            }
          },
          {
            name: 'list_environments',
            description: 'List all available environments',
            inputSchema: {
              type: 'object',
              properties: {}
            }
          },
          {
            name: 'get_products',
            description: 'Get products from current environment',
            inputSchema: {
              type: 'object',
              properties: {
                environment: {
                  type: 'string',
                  enum: Object.keys(this.environments),
                  description: 'Optional: specify environment for this request'
                },
                per_page: {
                  type: 'number',
                  description: 'Number of products to retrieve'
                },
                page: {
                  type: 'number',
                  description: 'Page number'
                }
              }
            }
          },
          {
            name: 'get_orders',
            description: 'Get orders from current environment',
            inputSchema: {
              type: 'object',
              properties: {
                environment: {
                  type: 'string',
                  enum: Object.keys(this.environments),
                  description: 'Optional: specify environment for this request'
                },
                per_page: {
                  type: 'number',
                  description: 'Number of orders to retrieve'
                },
                page: {
                  type: 'number',
                  description: 'Page number'
                }
              }
            }
          }
        ]
      };
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'switch_environment':
            this.currentEnvironment = args.environment;
            return {
              content: [
                {
                  type: 'text',
                  text: `Switched to environment: ${args.environment} (${this.environments[args.environment].url})`
                }
              ]
            };

          case 'list_environments':
            const envList = Object.entries(this.environments).map(([name, config]) => 
              `${name}: ${config.url} ${name === this.currentEnvironment ? '(current)' : ''}`
            ).join('\n');
            
            return {
              content: [
                {
                  type: 'text',
                  text: `Available environments:\n${envList}`
                }
              ]
            };

          case 'get_products':
            const productsApi = this.getWooCommerceApi(args.environment);
            const products = await productsApi.get('products', {
              per_page: args.per_page || 10,
              page: args.page || 1
            });
            
            return {
              content: [
                {
                  type: 'text',
                  text: `Products from ${args.environment || this.currentEnvironment}:\n${JSON.stringify(products.data, null, 2)}`
                }
              ]
            };

          case 'get_orders':
            const ordersApi = this.getWooCommerceApi(args.environment);
            const orders = await ordersApi.get('orders', {
              per_page: args.per_page || 10,
              page: args.page || 1
            });
            
            return {
              content: [
                {
                  type: 'text',
                  text: `Orders from ${args.environment || this.currentEnvironment}:\n${JSON.stringify(orders.data, null, 2)}`
                }
              ]
            };

          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [
            {
              type: 'text',
              text: `Error: ${error.message}`
            }
          ],
          isError: true
        };
      }
    });
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('Dynamic WooCommerce MCP server running on stdio');
  }
}

const server = new DynamicWooCommerceMCPServer();
server.run().catch(console.error);