import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';

const plans = [
  {
    name: 'Básico',
    price: '29',
    description: 'Ideal para pymes pequeñas',
    features: [
      'Hasta 100 cobros mensuales',
      'Recordatorios automáticos',
      'Soporte por email',
      'Dashboard básico',
    ],
  },
  {
    name: 'Pro',
    price: '79',
    description: 'Para negocios medianos',
    features: [
      'Hasta 500 cobros mensuales',
      'Recordatorios personalizados',
      'Soporte prioritario',
      'Dashboard avanzado',
      'API access',
    ],
    popular: true,
  },
  {
    name: 'Premium',
    price: '199',
    description: 'Servicios avanzados',
    features: [
      'Cobros ilimitados',
      'Atención personalizada',
      'Soporte 24/7',
      'Dashboard personalizado',
      'API access',
      'Reportes personalizados',
    ],
  },
];

export const Pricing = () => {
  // const handlePlanSelection = (plan: typeof plans[0]) => {
  //   navigate("/billing", { state: { plan } });
  // };

  return (
    <div className="py-20">
      <div className="container mx-auto px-6">
        <h2 className="mb-12 text-center text-3xl font-bold">
          Planes y Precios
        </h2>
        <div className="grid gap-8 md:grid-cols-3">
          {plans.map((plan) => (
            <div
              key={plan.name}
              className={`rounded-lg bg-white p-8 ${
                plan.popular
                  ? 'ring-primary relative shadow-lg ring-2'
                  : 'border shadow-sm'
              }`}
            >
              {plan.popular && (
                <span className="bg-primary absolute right-8 top-0 -translate-y-1/2 rounded-full px-3 py-1 text-sm text-white">
                  Popular
                </span>
              )}
              <h3 className="mb-2 text-2xl font-bold">{plan.name}</h3>
              <p className="mb-4 text-gray-600">{plan.description}</p>
              <div className="mb-6">
                <span className="text-4xl font-bold">${plan.price}</span>
                <span className="text-gray-600">/mes</span>
              </div>
              <ul className="mb-8 space-y-3">
                {plan.features.map((feature) => (
                  <li key={feature} className="flex items-center gap-2">
                    <Check className="text-primary h-5 w-5" />
                    <span>{feature}</span>
                  </li>
                ))}
              </ul>
              <Button
                className={`w-full text-white ${
                  plan.popular
                    ? 'bg-primary hover:bg-primary-dark'
                    : 'bg-gray-800 hover:bg-gray-900'
                }`}
                onClick={() => {}}
              >
                Comenzar
              </Button>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
