// GraphQL Schema definitions for the frontend

export const typeDefs = `
  type Customer {
    id: ID!
    name: String!
    email: String!
    phone: String
    company: String
    status: String!
    isVip: Boolean!
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

  type Lead {
    id: ID!
    name: String
    email: String
    phone: String
    company: String
    status: String!
    createdAt: String
    updatedAt: String
  }

  type CallLog {
    id: ID!
    targetType: String!
    targetId: String!
    targetName: String!
    targetPhone: String!
    status: String!
    startedAt: String!
    endedAt: String
    durationSeconds: Int
    isActive: Boolean!
  }

  type EmailCampaignRecipient {
    id: ID!
    recipientType: String!
    recipientId: String!
    recipientName: String!
    recipientEmail: String!
    status: String!
    createdAt: String!
    updatedAt: String!
    sentAt: String
    openedAt: String
    completedAt: String
  }

  type EmailCampaign {
    id: ID!
    name: String!
    subject: String!
    content: String!
    targetType: String!
    targetSegment: String!
    status: String!
    createdAt: String!
    updatedAt: String!
    startedAt: String
    completedAt: String
    recipientCount: Int!
    newCount: Int!
    sendingCount: Int!
    openedCount: Int!
    completedCount: Int!
    progressPercent: Int!
    recipients: [EmailCampaignRecipient!]!
  }

  type AppSetting {
    id: ID!
    settingKey: String!
    settingValue: String!
    description: String
    createdAt: String
    updatedAt: String
  }

  type AppUser {
    id: ID!
    name: String!
    email: String!
    role: String!
    status: String!
    avatarColor: String
    initials: String!
    createdAt: String
    updatedAt: String
  }

  type Activity {
    id: ID!
    entityType: String!
    action: String!
    entityId: String
    message: String!
    createdAt: String!
  }

  type Notification {
    id: ID!
    userId: String!
    activityId: String
    isRead: Boolean!
    createdAt: String!
    activity: Activity
  }

  type SearchResultItem {
    id: String!
    type: String!
    name: String!
    email: String
    subtitle: String
  }

  type SearchResults {
    customers: [SearchResultItem!]!
    leads: [SearchResultItem!]!
    deals: [SearchResultItem!]!
    inventoryItems: [SearchResultItem!]!
    totalResults: Int!
  }

  type Query {
    hello(name: String): String
    callLogs(limit: Int): [CallLog!]!
    activeCall: CallLog
    emailCampaigns(limit: Int): [EmailCampaign!]!
    emailCampaign(id: ID!): EmailCampaign
    customers: [Customer!]!
    customerCount: Int!
    customer(id: ID!): Customer
    contacts: [Contact!]!
    contactCount: Int!
    contact(id: ID!): Contact
    deals: [Deal!]!
    dealCount: Int!
    deal(id: ID!): Deal
    leads: [Lead!]!
    leadCount: Int!
    lead(id: ID!): Lead
    settings: [AppSetting!]!
    setting(key: String!): AppSetting
    users: [AppUser!]!
    activeUsers: [AppUser!]!
    user(id: ID!): AppUser
    userCount: Int!
    notifications(userId: String!, limit: Int): [Notification!]!
    unreadNotificationCount(userId: String!): Int!
    search(query: String!, limit: Int): SearchResults!
  }

  type Mutation {
    createCustomer(name: String!, email: String!, phone: String, company: String, status: String, isVip: Boolean): Customer!
    updateCustomer(id: ID!, name: String, email: String, phone: String, company: String, status: String, isVip: Boolean): Customer!
    deleteCustomer(id: ID!): Boolean!
    createLead(name: String, email: String, phone: String, company: String, status: String): Lead!
    updateLead(id: ID!, name: String, email: String, phone: String, company: String, status: String): Lead!
    deleteLead(id: ID!): Boolean!
    startCall(targetType: String!, targetId: String!): CallLog!
    stopCall(id: ID!): CallLog!
    createEmailCampaign(name: String!, subject: String!, content: String!, targetType: String!, targetSegment: String!): EmailCampaign!
    updateSetting(key: String!, value: String!, description: String): AppSetting!
    updateSettings(settings: [SettingInput!]!): [AppSetting!]!
    deleteSetting(key: String!): Boolean!
    createUser(name: String!, email: String!, role: String, status: String, avatarColor: String): AppUser!
    updateUser(id: ID!, name: String, email: String, role: String, status: String, avatarColor: String): AppUser!
    deleteUser(id: ID!): Boolean!
    markNotificationRead(id: String!): Boolean!
    markAllNotificationsRead(userId: String!): Boolean!
  }

  input SettingInput {
    key: String!
    value: String!
    description: String
  }
`;
