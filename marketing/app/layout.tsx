import type { Metadata } from 'next';
import { Inter, Space_Grotesk } from 'next/font/google';
import './globals.css';

const sans = Inter({
  subsets: ['latin'],
  variable: '--font-sans',
  display: 'swap',
});

const display = Space_Grotesk({
  subsets: ['latin'],
  variable: '--font-display',
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'Rushly — The AI Logistics Operating System',
  description:
    'One platform for every shipment, every warehouse, every merchant, every courier. AI-powered OMS, WMS, fulfillment, last-mile and fleet — built for enterprise logistics.',
  metadataBase: new URL('https://rushly.tech'),
  openGraph: {
    title: 'Rushly — The AI Logistics Operating System',
    description:
      'One platform for every shipment, every warehouse, every merchant, every courier.',
    type: 'website',
    url: 'https://rushly.tech',
  },
  twitter: { card: 'summary_large_image' },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${sans.variable} ${display.variable} dark`}>
      <body className="bg-bg text-white antialiased">{children}</body>
    </html>
  );
}
