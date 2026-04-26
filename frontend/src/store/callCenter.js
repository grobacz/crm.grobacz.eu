import { defineStore } from 'pinia'
import { ref } from 'vue'
import { graphqlRequest } from '../graphql/client'

const CALL_LOG_FIELDS = `
  id
  targetType
  targetId
  targetName
  targetPhone
  status
  startedAt
  endedAt
  durationSeconds
  isActive
`

const ACTIVE_CALL_QUERY = `
  query ActiveCall {
    activeCall {
      ${CALL_LOG_FIELDS}
    }
  }
`

const CALL_LOGS_QUERY = `
  query CallLogs($limit: Int!) {
    callLogs(limit: $limit) {
      ${CALL_LOG_FIELDS}
    }
  }
`

const START_CALL_MUTATION = `
  mutation StartCall($targetType: String!, $targetId: String!) {
    startCall(targetType: $targetType, targetId: $targetId) {
      ${CALL_LOG_FIELDS}
    }
  }
`

const STOP_CALL_MUTATION = `
  mutation StopCall($id: String!) {
    stopCall(id: $id) {
      ${CALL_LOG_FIELDS}
    }
  }
`

function normalizeCallLog(callLog) {
  if (!callLog) {
    return null
  }

  return {
    ...callLog,
    durationSeconds: typeof callLog.durationSeconds === 'number' ? callLog.durationSeconds : null,
    isActive: Boolean(callLog.isActive)
  }
}

export const useCallCenterStore = defineStore('callCenter', () => {
  const activeCall = ref(null)
  const callLogs = ref([])
  const isLoadingActiveCall = ref(false)
  const isLoadingLogs = ref(false)
  const startingTargetKey = ref('')
  const stoppingCallId = ref('')
  const hasLoadedActiveCall = ref(false)
  const hasLoadedLogs = ref(false)

  function getTargetKey(targetType, targetId) {
    return `${targetType}:${targetId}`
  }

  function isTargetActive(targetType, targetId) {
    return activeCall.value?.targetType === targetType && activeCall.value?.targetId === targetId
  }

  function upsertCallLog(callLog) {
    const normalizedCallLog = normalizeCallLog(callLog)

    if (!normalizedCallLog) {
      return
    }

    const existingIndex = callLogs.value.findIndex((entry) => entry.id === normalizedCallLog.id)

    if (existingIndex === -1) {
      callLogs.value = [normalizedCallLog, ...callLogs.value]
      return
    }

    callLogs.value = callLogs.value.map((entry) => {
      return entry.id === normalizedCallLog.id ? normalizedCallLog : entry
    })
  }

  async function loadActiveCall(force = false) {
    if (hasLoadedActiveCall.value && !force) {
      return activeCall.value
    }

    isLoadingActiveCall.value = true

    try {
      const data = await graphqlRequest(ACTIVE_CALL_QUERY)
      activeCall.value = normalizeCallLog(data.activeCall)
      hasLoadedActiveCall.value = true

      return activeCall.value
    } finally {
      isLoadingActiveCall.value = false
    }
  }

  async function loadCallLogs(limit = 25, force = false) {
    if (hasLoadedLogs.value && !force) {
      return callLogs.value
    }

    isLoadingLogs.value = true

    try {
      const data = await graphqlRequest(CALL_LOGS_QUERY, { limit })
      callLogs.value = (data.callLogs ?? []).map(normalizeCallLog)
      hasLoadedLogs.value = true

      return callLogs.value
    } finally {
      isLoadingLogs.value = false
    }
  }

  async function startCall(targetType, targetId) {
    const targetKey = getTargetKey(targetType, targetId)
    startingTargetKey.value = targetKey

    try {
      const data = await graphqlRequest(START_CALL_MUTATION, { targetType, targetId })
      const startedCall = normalizeCallLog(data.startCall)
      activeCall.value = startedCall
      upsertCallLog(startedCall)

      return startedCall
    } finally {
      startingTargetKey.value = ''
    }
  }

  async function stopCall(callId = activeCall.value?.id) {
    if (!callId) {
      return null
    }

    stoppingCallId.value = callId

    try {
      const data = await graphqlRequest(STOP_CALL_MUTATION, { id: callId })
      const stoppedCall = normalizeCallLog(data.stopCall)

      if (activeCall.value?.id === stoppedCall?.id) {
        activeCall.value = null
      }

      upsertCallLog(stoppedCall)

      return stoppedCall
    } finally {
      stoppingCallId.value = ''
    }
  }

  return {
    activeCall,
    callLogs,
    hasLoadedActiveCall,
    hasLoadedLogs,
    isLoadingActiveCall,
    isLoadingLogs,
    startingTargetKey,
    stoppingCallId,
    getTargetKey,
    isTargetActive,
    loadActiveCall,
    loadCallLogs,
    startCall,
    stopCall
  }
})
