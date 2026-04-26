import { GraphQLClient } from 'graphql-request'

const GRAPHQL_ENDPOINT = import.meta.env.VITE_API_URL || '/graphql'

export const graphqlClient = new GraphQLClient(GRAPHQL_ENDPOINT, {
  headers: {
    'Content-Type': 'application/json',
  },
})

export const graphqlRequest = async (query, variables = {}) => {
  try {
    const data = await graphqlClient.request(query, variables)
    return data
  } catch (error) {
    console.error('GraphQL Error:', error)
    throw error
  }
}
