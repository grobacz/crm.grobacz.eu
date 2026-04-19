// GraphQL Schema definitions for the frontend

export const typeDefs = `
  type Customer {
    id: ID!
    name: String!
    email: String!
    phone: String
    company: String
  }

  type Contact {
    id: ID!
    name: String!
    email: String
    phone: String
    title: String
    isPrimary: Boolean!
  }

  type Deal {
    id: ID!
    title: String!
    value: Float
    status: String
    closeDate: String
  }

  type Query {
    hello(name: String): String
    customers: [Customer!]!
    customer(id: ID!): Customer
    contacts: [Contact!]!
    deals: [Deal!]!
  }

  type Mutation {
    createCustomer(name: String!, email: String!, phone: String, company: String): Customer!
    updateCustomer(id: ID!, name: String, email: String, phone: String, company: String): Customer!
    deleteCustomer(id: ID!): Boolean!
  }
`;
