<script setup>
import {onMounted, watch} from 'vue';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';

const props = defineProps({
  package_name: {
    type: String,
    required: true,
  },
});

const appViewWindow = useAppViewWindowStore();

function activate() {
  appViewWindow.openFullscreen(props.package_name);
}

onMounted(activate);

watch(() => props.package_name, activate);
</script>
