<template>
  <div class="managerBoot" role="status" aria-live="polite" aria-label="در حال بارگذاری">
    <div class="managerBoot__backdrop" aria-hidden="true">
      <span class="managerBoot__orb managerBoot__orb--1"/>
      <span class="managerBoot__orb managerBoot__orb--2"/>
      <span class="managerBoot__orb managerBoot__orb--3"/>
    </div>

    <div class="managerBoot__panel">
      <div class="managerBoot__logo-stage">
        <span class="managerBoot__ring managerBoot__ring--outer"/>
        <span class="managerBoot__ring managerBoot__ring--inner"/>
        <img src="@/assets/media/pinoox/logo.png" alt="Pinoox" class="managerBoot__logo" width="72" height="72">
      </div>

      <p class="managerBoot__title">pinoox manager</p>
      <p class="managerBoot__status">
        در حال بارگذاری
        <span class="managerBoot__dots" aria-hidden="true">
          <span>.</span><span>.</span><span>.</span>
        </span>
      </p>
    </div>
  </div>
</template>
