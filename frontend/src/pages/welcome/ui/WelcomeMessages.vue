<script setup lang="ts">
import { mdiNote, mdiBookmark, mdiTag, mdiCurrencyUsdOff, mdiGoogleChrome } from '@mdi/js'
import WelcomeMessageContainer from './WelcomeMessageContainer.vue'
</script>

<template>
  <div class="wrapper">
    <WelcomeMessageContainer
      :icon="mdiNote"
      title="メモ"
      message="メモをmd形式でかいて保存できます"
    />
    <WelcomeMessageContainer
      :icon="mdiBookmark"
      title="ブックマーク"
      message="ブックマークを保存できます"
    />
    <WelcomeMessageContainer
      :icon="mdiTag"
      title="タグをつけて管理"
      message="タグをつけて整理したり検索などで探すことができます"
    />
    <WelcomeMessageContainer :icon="mdiCurrencyUsdOff" title="無料で使える" />
    <WelcomeMessageContainer :icon="mdiGoogleChrome" title="アドオン">
      <template v-slot:message>
        <p>chromeアドオンを使えばブックマーク保存がもっと楽に!</p>
        <p>
          <a
            class="a-blue"
            target="_blank"
            rel="noopener noreferrer"
            href="https://chrome.google.com/webstore/detail/sundlf-bookmark-addon/mfcobcdpjbgnpbkhbbfaabkkphpceoka"
            >ダウンロードはこちらから
          </a>
        </p>
      </template>
    </WelcomeMessageContainer>
  </div>
</template>

<style scoped>
.wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  padding: 1px;
}
.wrapper > * {
  border-top: solid 1px;
  border-color: var(--usual-color);
}

@media (max-width: 480px) {
  .wrapper {
    grid-template-columns: 1fr;
  }
}
</style>
