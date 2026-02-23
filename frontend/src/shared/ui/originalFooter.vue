<template>
  <footer class="theme-color-bg">
    <a
      target="_blank"
      rel="noopener noreferrer"
      href="https://docs.google.com/document/d/e/2PACX-1vQRfqPmWcI2irs1HpRBOjA9lyo2CiIFWRBpWY2lmHnMM8gWZUEmng57BEs1t-VC5Bd_kSCHhmG9gmAA/pub"
    >
      利用規約
    </a>
    <a
      target="_blank"
      rel="noopener noreferrer"
      href="https://docs.google.com/document/d/e/2PACX-1vQCW5pRoXeXHiZJ-vz8MImLVm-XTViLIdy1TxTBtsbAAzYb4MpPEaEFucHaWnpzDkI905s5AeW6rui3/pub"
    >
      プライバシーポリシー
    </a>
    <!-- <a target="_blank" rel="noopener noreferrer" href="https://forms.gle/9tXeZcXmqH2zR6SP9">
      問い合わせ
    </a> -->
  </footer>
</template>

<style scoped>
footer {
  margin-top: auto;
  width: 100%;
  padding: 1rem 0;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  a {
    color: black;
    text-decoration-line: underline;
  }
  @media (max-width: 450px) {
    flex-flow: column;
  }
}
</style>
