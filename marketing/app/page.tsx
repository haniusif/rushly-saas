import { Nav } from '@/components/sections/nav';
import { Hero } from '@/components/sections/hero';
import { TrustedBy } from '@/components/sections/trusted-by';
import { PlatformOverview } from '@/components/sections/platform-overview';
import { Products } from '@/components/sections/products';
import { Integrations } from '@/components/sections/integrations';
import { AIAutomation } from '@/components/sections/ai-automation';
import { Solutions } from '@/components/sections/solutions';
import { WarehouseShowcase } from '@/components/sections/warehouse-showcase';
import { DashboardGallery } from '@/components/sections/dashboard-gallery';
import { Performance } from '@/components/sections/performance';
import { Testimonials } from '@/components/sections/testimonials';
import { Pricing } from '@/components/sections/pricing';
import { FAQ } from '@/components/sections/faq';
import { FinalCTA } from '@/components/sections/final-cta';
import { Footer } from '@/components/sections/footer';
import { MouseGlow } from '@/components/motion/mouse-glow';

export default function Home() {
  return (
    <main className="relative">
      <MouseGlow />
      <Nav />
      <Hero />
      <TrustedBy />
      <PlatformOverview />
      <Products />
      <Integrations />
      <AIAutomation />
      <Solutions />
      <WarehouseShowcase />
      <DashboardGallery />
      <Performance />
      <Testimonials />
      <Pricing />
      <FAQ />
      <FinalCTA />
      <Footer />
    </main>
  );
}
