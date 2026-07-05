<template>
  <PageSection title="کاربران">
    <div v-if="isLoading && !hasCachedData" class="appManagerSectionLoading">
      <WidgetLoading/>
    </div>

    <div
        v-else
        class="appManagerSection"
        :class="{ 'is-refreshing': isRefreshing }"
    >
      <div v-if="isRefreshing" class="appManagerSection__refresh" aria-hidden="true">
        <WidgetLoading/>
      </div>

      <div v-if="users.length" class="appManagerUsers overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-800">
          <thead>
          <tr>
            <th class="px-4 py-2 text-right">نام</th>
            <th class="px-4 py-2 text-right">ایمیل</th>
            <th class="px-4 py-2 text-right">وضعیت</th>
            <th class="px-4 py-2 text-right">تاریخ ثبت</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="user in users" :key="user.email">
            <td class="px-4 py-2">{{ user.full_name }}</td>
            <td class="px-4 py-2 ltr">{{ user.email }}</td>
            <td class="px-4 py-2">{{ user.status_fa }}</td>
            <td class="px-4 py-2">{{ user.register_date_fa }}</td>
          </tr>
          </tbody>
        </table>
      </div>

      <PageEmpty v-else title="کاربری یافت نشد"/>
    </div>
  </PageSection>
</template>

<script setup>
import {computed} from 'vue';
import {userAPI} from '@api/user.js';
import {unwrapResponse} from '@utils/helpers/apiHelper.js';
import WidgetLoading from '@/views/components/desktop-widgets/WidgetLoading.vue';
import {useAppManagerSectionData} from '@/views/composables/useAppManagerSectionData.js';

const props = defineProps({packageName: String});

const packageName = computed(() => props.packageName);

const {
  items: users,
  isLoading,
  isRefreshing,
  hasCachedData,
} = useAppManagerSectionData('users', packageName, async (name) => {
  const response = await userAPI.getUsers(name);
  return unwrapResponse(response) ?? [];
});
</script>
