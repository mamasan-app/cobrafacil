import { UserPlus, Settings, CreditCard } from 'lucide-react';

const steps = [
  {
    icon: UserPlus,
    title: 'Regístrate',
    description: 'Crea tu cuenta en menos de 2 minutos',
  },
  {
    icon: Settings,
    title: 'Configura',
    description: 'Personaliza tus opciones de cobro',
  },
  {
    icon: CreditCard,
    title: 'Cobra',
    description: 'Recibe pagos automáticamente',
  },
];

export const HowItWorks = () => {
  return (
    <div className="py-20">
      <div className="container mx-auto px-6">
        <h2 className="mb-12 text-center text-3xl font-bold">
          ¿Cómo funciona?
        </h2>
        <div className="grid gap-8 md:grid-cols-3">
          {steps.map((step, index) => (
            <div key={step.title} className="relative text-center">
              {index < steps.length && (
                <div className="bg-primary/20 absolute right-0 top-8 hidden h-0.5 w-full md:block" />
              )}
              <div className="bg-primary relative z-10 mb-4 inline-block rounded-full p-4">
                <step.icon className="h-8 w-8 text-white" />
              </div>
              <h3 className="mb-2 text-xl font-semibold">{step.title}</h3>
              <p className="text-gray-600">{step.description}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
