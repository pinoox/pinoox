<template>
  <Page title="حساب کاربری">
    <PageSection title="تصویر پروفایل">
      <div class="flex items-center gap-6">
        <img
            :src="profileAvatar"
            class="w-24 h-24 rounded-full object-cover cursor-pointer border-2 border-white/30"
            @click="avatarInput.click()"
            alt="avatar"
        />
        <input ref="avatarInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="changeAvatar"/>
        <div class="flex gap-3">
          <Button label="تغییر تصویر" variant="light" outline @click="avatarInput.click()"/>
          <Button v-if="authStore.user.isAvatar" label="حذف" variant="dark" outline @click="deleteAvatar"/>
        </div>
      </div>
    </PageSection>

    <PageSection title="اطلاعات حساب" @keyup.enter="saveInfo">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <Input v-model="params.fname" label="نام" placeholder="نام"/>
        <Input v-model="params.lname" label="نام خانوادگی" placeholder="نام خانوادگی"/>
        <Input v-model="params.username" label="نام کاربری" placeholder="نام کاربری"/>
        <Input v-model="params.email" label="ایمیل" placeholder="ایمیل" direction="ltr"/>
      </div>
      <div class="mt-4">
        <Button label="ذخیره" variant="primary" :is-loading="isLoadingInfo" @click="saveInfo"/>
      </div>
    </PageSection>

    <PageSection title="رمز عبور" @keyup.enter="savePassword">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <Input v-model="params.old_password" type="password" label="رمز فعلی" placeholder="رمز فعلی"/>
        <Input v-model="params.new_password" type="password" label="رمز جدید" placeholder="رمز جدید"/>
        <Input v-model="params.valid_password" type="password" label="تکرار رمز جدید" placeholder="تکرار رمز جدید"/>
      </div>
      <div class="mt-4">
        <Button label="ذخیره رمز" variant="primary" :is-loading="isLoadingPass" @click="savePassword"/>
      </div>
    </PageSection>
  </Page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { userAPI } from "@api/user.js";
import { useAuthStore } from "@/stores/modules/auth.js";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";
import { userAvatarSrc } from "@utils/helpers/userAvatar.js";

const authStore = useAuthStore();
const profileAvatar = computed(() => userAvatarSrc(authStore.user));
const avatarInput = ref(null);
const isLoadingInfo = ref(false);
const isLoadingPass = ref(false);

const params = ref({
  fname: '',
  lname: '',
  username: '',
  email: '',
  old_password: '',
  new_password: '',
  valid_password: '',
});

onMounted(() => {
  params.value = {
    ...params.value,
    fname: authStore.user.fname,
    lname: authStore.user.lname,
    username: authStore.user.username,
    email: authStore.user.email,
  };
});

const deleteAvatar = async () => {
  const response = await userAPI.deleteAvatar();
  const result = unwrapResponse(response) ?? {};
  authStore.setUser({ ...authStore.user, ...result, isAvatar: false });
};

const changeAvatar = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('avatar', file);
  const response = await userAPI.changeAvatar(formData);
  const result = unwrapResponse(response) ?? {};
  authStore.setUser({ ...authStore.user, ...result, isAvatar: true });
};

const saveInfo = async () => {
  isLoadingInfo.value = true;
  try {
    await userAPI.changeInfo({
      fname: params.value.fname,
      lname: params.value.lname,
      username: params.value.username,
      email: params.value.email,
    });
    authStore.setUser({ ...authStore.user, ...params.value });
  } finally {
    isLoadingInfo.value = false;
  }
};

const savePassword = async () => {
  isLoadingPass.value = true;
  try {
    await userAPI.changePassword({
      old_password: params.value.old_password,
      new_password: params.value.new_password,
      valid_password: params.value.valid_password,
    });
    params.value.old_password = '';
    params.value.new_password = '';
    params.value.valid_password = '';
  } finally {
    isLoadingPass.value = false;
  }
};
</script>
