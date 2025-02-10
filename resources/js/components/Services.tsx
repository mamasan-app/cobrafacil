import { CreditCard, Bell, Globe, LayoutDashboard } from 'lucide-react';
import { Button } from '@/components/ui/button';

const services = [
  {
    icon: CreditCard,
    title: 'Planes de Pago',
    description: 'Configura planes mensuales, trimestrales o anuales',
  },
  {
    icon: Bell,
    title: 'Domiciliación Bancaria',
    description: 'Automatiza los cobros recurrentes de tus clientes',
  },
  {
    icon: Globe,
    title: 'Conciliación Multimoneda',
    description: 'Maneja bolívares y divisas',
  },
  {
    icon: LayoutDashboard,
    title: 'Panel de Control',
    description: 'Gestiona todos tus cobros desde un solo lugar',
  },
];

export const Services = () => {
  return (
    <div className="bg-gray-50 py-20">
      <div className="container mx-auto px-6">
        <h2 className="mb-12 text-center text-3xl font-bold">
          Nuestros Servicios
        </h2>
        <div className="grid gap-8 md:grid-cols-2">
          {services.map((service) => (
            <div
              key={service.title}
              className="rounded-lg bg-white p-8 shadow-sm transition-shadow hover:shadow-md"
            >
              <div className="flex items-start gap-4">
                <div className="bg-primary/10 rounded-lg p-3">
                  <service.icon className="text-primary h-6 w-6" />
                </div>
                <div>
                  <h3 className="mb-2 text-xl font-semibold">
                    {service.title}
                  </h3>
                  <p className="mb-4 text-gray-600">{service.description}</p>
                  <Button variant="link" className="text-primary p-0">
                    <a href="#contact">Solicitar más información →</a>
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
