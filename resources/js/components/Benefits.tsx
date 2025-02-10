import { Zap, CheckCircle, Globe } from "lucide-react";

const benefits = [
  {
    icon: Zap,
    title: "Rapidez",
    description: "Cobra automáticamente sin perder tiempo",
  },
  {
    icon: CheckCircle,
    title: "Simplicidad",
    description: "Interfaz intuitiva y fácil de usar",
  },
  {
    icon: Globe,
    title: "Multimoneda",
    description: "Maneja bolívares y divisas",
  },
];

export const Benefits = () => {
  return (
    <div className="py-20 bg-gray-50">
      <div className="container mx-auto px-6">
        <h2 className="text-3xl font-bold text-center mb-12">
          Beneficios que hacen la diferencia
        </h2>
        <div className="grid md:grid-cols-3 gap-8">
          {benefits.map((benefit) => (
            <div key={benefit.title} className="text-center">
              <div className="inline-block p-4 bg-primary/10 rounded-full mb-4">
                <benefit.icon className="w-8 h-8 text-primary" />
              </div>
              <h3 className="text-xl font-semibold mb-2">{benefit.title}</h3>
              <p className="text-gray-600">{benefit.description}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};