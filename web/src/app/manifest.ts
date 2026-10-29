import type { MetadataRoute } from "next";

// Сайт можно «установить» на телефон как приложение (до выхода нативного).
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "Avto — оригинальные запчасти ВАЗ",
    short_name: "Avto",
    description: "Оригинальные запчасти ВАЗ (LADA): поиск по артикулу и подбор по автомобилю.",
    lang: "ru",
    start_url: "/",
    display: "standalone",
    background_color: "#f6f6f4",
    theme_color: "#ea580c",
    icons: [
      { src: "/icons/icon-192.png", sizes: "192x192", type: "image/png" },
      { src: "/icons/icon-512.png", sizes: "512x512", type: "image/png" },
      { src: "/icons/icon-maskable-512.png", sizes: "512x512", type: "image/png", purpose: "maskable" },
    ],
  };
}
