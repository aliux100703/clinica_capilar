import type { Metadata } from "next";
import { Header } from "@/components/header";
import { Footer } from "@/components/footer";
import { pageMetadata, siteUrl } from "@/lib/seo";
import "./globals.css";
import { getLayoutSettings } from '@/lib/cms';

const fallbackMetadata: Metadata = {
  ...pageMetadata(
    "Clínica capilar | Una nueva perspectiva para tu cabello",
    "Explora una propuesta de cuidado capilar centrada en la valoración individual y la información transparente. Clínica en desarrollo: nombre y ciudad por definir.",
    "/",
  ),
  ...(siteUrl ? { metadataBase: siteUrl } : {}),
  icons: { icon: "/icon.svg", shortcut: "/icon.svg" },
};

export async function generateMetadata(): Promise<Metadata> {
  const settings = await getLayoutSettings();
  return {...fallbackMetadata,...pageMetadata(settings.seo_title || settings.trade_name,settings.seo_description || '', '/')};
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const settings = await getLayoutSettings();
  return <html lang="es"><body>
    <a className="skip-link" href="#contenido">Saltar al contenido</a>
    <Header settings={settings} />
    {children}
    <Footer settings={settings} />
  </body></html>;
}
