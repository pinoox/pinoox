<template>
  <SimpleModal :title="title" size="sm" class="modalRoutes">
    <div v-if="currentStep === 1" class="form">
      <Input
          type="text"
          v-model="params.route"
          label="آدرس مسیر"
          direction="ltr"
          placeholder="نام مسیر"
          prefix="pinoox.com/"
      />
      <div class="flex justify-end mt-4">
        <Button @click="goToNextStep" :disabled="!params.route" label="ادامه" variant="primary"/>
      </div>
    </div>

    <div v-else class="form">
      <Input
          type="text"
          v-model="searchQuery"
          label="جستجو..."
          placeholder="نام اپلیکیشن را بنویسید"
      />
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6 mt-12">
        <div
            v-for="app in filteredApps"
            :key="app.name"
            @click="selectApp(app)"
            class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
            :class="{
            'opacity-100': app.name === params.selectedApp?.name,
            'opacity-40': app.name !== params.selectedApp?.name && params.selectedApp
          }"
        >
          <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl flex items-center justify-center shadow-lg">
            <img :src="app.icon" :alt="app.name" class="w-16 h-16 md:w-20 md:h-20"/>
          </div>
          <span class="text-sm text-gray-400">{{ app.name }}</span>
        </div>
      </div>
      <div class="flex justify-between mt-4">
        <Button @click="goToPreviousStep" label="بازگشت" variant="dark"/>
        <Button @click="save" :disabled="!params.selectedApp" label="ذخیره" variant="primary"/>
      </div>
    </div>
  </SimpleModal>
</template>

<script setup>
import {ref, computed} from 'vue';
import {closeModal} from '@kolirt/vue-modal';

const props = defineProps({
  payload: {
    type: Object,
    default: null,
  },
});

const params = ref({
  route: '',
  selectedApp: null,
});

const searchQuery = ref('');
const currentStep = ref(1);

const apps = ref([
  {name: 'اتوماسیون داخلی', icon: new URL('@/assets/media/icons/1.png', import.meta.url).href},
  {name: 'حسابداری شخصی', icon: new URL('@/assets/media/icons/2.png', import.meta.url).href},
  // سایر برنامه‌ها را در اینجا اضافه کنید
]);

const filteredApps = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) {
    return apps.value;
  }
  return apps.value.filter((app) =>
      app.name.toLowerCase().includes(query)
  );
});

const selectApp = (app) => {
  if (app === params.value.selectedApp) params.value.selectedApp = null;
  else params.value.selectedApp = app;
};

const goToNextStep = () => {
  currentStep.value = 2;
};

const goToPreviousStep = () => {
  currentStep.value = 1;
};

const save = () => {
  console.log('Route:', params.value.route);
  console.log('Selected App:', params.value.selectedApp);
  closeModal();
};

const title = computed(() => (props.payload ? 'ویرایش' : 'افزودن مسیر جدید'));
</script>