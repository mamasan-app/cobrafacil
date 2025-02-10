import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import { useToast } from '@/components/ui/use-toast';

const GetStarted = () => {
  const { toast } = useToast();
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !phone) {
      toast({
        title: 'Error',
        description: 'Por favor completa todos los campos',
        variant: 'destructive',
      });
      return;
    }
    // Here you would typically send this data to your backend
    toast({
      title: '¡Gracias por tu interés!',
      description: 'Nos pondremos en contacto contigo pronto.',
    });
    // navigate("/");
  };

  return (
    <div className="min-h-screen bg-white py-20">
      <div className="container mx-auto px-6">
        <div className="mx-auto max-w-4xl">
          <h1 className="mb-8 text-center text-4xl font-bold">
            ¿Cómo funciona CobraFácil?
          </h1>

          <div className="mb-16 space-y-8">
            <section className="rounded-lg bg-gray-50 p-8">
              <h2 className="mb-4 text-2xl font-semibold">Proceso Simple</h2>
              <div className="grid gap-6 md:grid-cols-3">
                <div className="text-center">
                  <div className="bg-primary/10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <span className="text-primary text-2xl font-bold">1</span>
                  </div>
                  <h3 className="mb-2 font-semibold">Regístrate</h3>
                  <p className="text-gray-600">
                    Completa el formulario con tus datos básicos
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-primary/10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <span className="text-primary text-2xl font-bold">2</span>
                  </div>
                  <h3 className="mb-2 font-semibold">Configura</h3>
                  <p className="text-gray-600">
                    Personaliza tus opciones de cobro según tus necesidades
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-primary/10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <span className="text-primary text-2xl font-bold">3</span>
                  </div>
                  <h3 className="mb-2 font-semibold">¡Comienza a cobrar!</h3>
                  <p className="text-gray-600">
                    Recibe pagos automáticamente en tu cuenta
                  </p>
                </div>
              </div>
            </section>

            <section className="rounded-lg bg-gray-50 p-8">
              <h2 className="mb-4 text-2xl font-semibold">Nuestras Tarifas</h2>
              <div className="space-y-4">
                <div className="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Básico</h3>
                    <p className="text-gray-600">
                      Ideal para pequeños negocios
                    </p>
                  </div>
                  <p className="text-primary text-xl font-bold">$29/mes</p>
                </div>
                <div className="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Pro</h3>
                    <p className="text-gray-600">
                      Para negocios en crecimiento
                    </p>
                  </div>
                  <p className="text-primary text-xl font-bold">$49/mes</p>
                </div>
                <div className="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Enterprise</h3>
                    <p className="text-gray-600">Soluciones personalizadas</p>
                  </div>
                  <p className="text-primary text-xl font-bold">Contactar</p>
                </div>
              </div>
            </section>

            <section className="rounded-lg bg-gray-50 p-8">
              <h2 className="mb-6 text-2xl font-semibold">
                ¿Listo para comenzar?
              </h2>
              <form
                onSubmit={handleSubmit}
                className="mx-auto max-w-md space-y-4"
              >
                <div>
                  <label
                    htmlFor="email"
                    className="mb-1 block text-sm font-medium text-gray-700"
                  >
                    Correo Electrónico
                  </label>
                  <Input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="tu@email.com"
                    className="w-full"
                  />
                </div>
                <div>
                  <label
                    htmlFor="phone"
                    className="mb-1 block text-sm font-medium text-gray-700"
                  >
                    Teléfono
                  </label>
                  <Input
                    id="phone"
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="+58 (424) 123-4567"
                    className="w-full"
                  />
                </div>
                <Button
                  type="submit"
                  className="bg-primary hover:bg-primary-dark w-full text-white"
                >
                  Comenzar Ahora
                </Button>
              </form>
            </section>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GetStarted;
