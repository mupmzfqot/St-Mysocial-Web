import { ref } from 'vue';

export const TeamStore = {
    state: ref([]),
    
    async fetchTeams() {
        const cachedTeams = localStorage.getItem('cached_teams');
        const cachedTimestamp = localStorage.getItem('teams_cache_timestamp');
        const currentTime = Date.now();

        // Check if cached teams exist and are less than 1 hour old
        if (cachedTeams && cachedTimestamp && 
            (currentTime - parseInt(cachedTimestamp) < 300)) {
            this.state.value = JSON.parse(cachedTeams);
            return this.state.value;
        }

        try {
            let response = await axios.get(route('team.get'));
            this.state.value = response.data;
            
            // Cache the teams and timestamp
            localStorage.setItem('cached_teams', JSON.stringify(response.data));
            localStorage.setItem('teams_cache_timestamp', currentTime.toString());

            return this.state.value;
        } catch (error) {
            console.error('Failed to fetch teams:', error);
            return [];
        }
    },

    getTeams() {
        return this.state.value;
    }
};