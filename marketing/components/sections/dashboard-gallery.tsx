import { SectionHeader } from '@/components/ui/eyebrow';
import { OrdersMock, ShipmentsMock, DriversMock, InventoryMock, FulfillmentMock, AnalyticsMock, RoutesMock, TrackingMock, FinanceMock } from '@/components/mockups/mini-dashboards';
import { Reveal } from '@/components/motion/reveal';

const items = [
  { comp: <OrdersMock />, name: 'Orders' },
  { comp: <ShipmentsMock />, name: 'Shipments' },
  { comp: <RoutesMock />, name: 'Routing' },
  { comp: <DriversMock />, name: 'Fleet' },
  { comp: <InventoryMock />, name: 'Inventory' },
  { comp: <FulfillmentMock />, name: 'Fulfillment' },
  { comp: <FinanceMock />, name: 'Finance' },
  { comp: <TrackingMock />, name: 'Tracking' },
  { comp: <AnalyticsMock />, name: 'Analytics' },
];

export function DashboardGallery() {
  return (
    <section className="relative py-28">
      <div className="container">
        <SectionHeader
          eyebrow="Dashboards"
          title={<>Beautiful, fast dashboards — designed for the people who run ops.</>}
          description="Nine surfaces, one design system. Same primitives across web, mobile, driver app and merchant portal."
        />
        <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {items.map((it, i) => (
            <Reveal key={it.name} delay={i * 0.04}>
              <div className="glass-strong rounded-3xl p-3 h-full">
                {it.comp}
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
