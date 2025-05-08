import { ref } from 'vue'
import axios from 'axios'

export function useUserStatus() {
    const status = ref(null)

    const getUserStatus = async (userId) => {
        try {
            const response = await axios.get(`/api/user-status/${userId}`)
            status.value = response.data.status
        } catch (error) {
            status.value = 'offline'
        }

        return status.value
    }

    return {
        status,
        getUserStatus
    }
}
