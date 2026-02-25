# PrimeVueでMDIアイコンを使う方法

vuetifyと違いprimevueでmdiを使う場合にはSvgIconコンポーネントを使う必要がある。

SvgIconコンポーネントは`main.ts`で読み込んでいるためどこでも呼び出せる

まず、使いたいアイコンのパスを`@mdi/js`からインポートする:

```vue
<script>
import { mdiHome } from "@mdi/js";
</script>

<template>
  <SvgIcon type="mdi" :path="mdiHome" />
</template>
```
