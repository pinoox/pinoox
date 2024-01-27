<template>
  <section class="section">
    <div class="container">

      <div class="section__market">
        <div class="section__market-content">
          <h2 class="headline">اپلیکیشن مورد نظر خودت رو پیدا کن</h2>
          <h2 class="gradient-text"> دانلود اپلکیشن‌های دلخواه از مارکت</h2>
          <p class="text-dark">منجر در نقش مرکز فرماندهی پینوکس این امکان را برای شما فراهم میکند که به شکلی گرافیکی
            و
            کاربرپسند وب سایت خود را ایجاد و مدیریت کنید.</p>

          <div class="btn btn-primary-outline">رفتن به مارکت</div>

        </div>
        <div class="section__market-solar">
          <Solar></Solar>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup>
import Solar from "@/views/components/solar.vue";
</script>