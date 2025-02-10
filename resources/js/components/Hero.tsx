import { Button } from '@/components/ui/button';
import Logo from '../../assets/img/logo-a.png';

export const Hero = () => {
  return (
    <div className="relative bg-white px-6 pb-20 pt-2">
      <div className="absolute right-4 top-4 flex gap-2">
        <Button
          variant="outline"
          className="border-primary text-primary hover:bg-primary/10"
        >
          <a href="tienda/ingresar">Iniciar Sesión</a>
        </Button>
        <Button className="bg-primary text-white hover:bg-primary-dark" asChild>
          <a href="/tienda/registrar">Registrarse</a>
        </Button>
      </div>
      <div className="text-center">
        <div className="mb-0 flex justify-center">
          <img src={Logo} alt="Cobra Fácil Logo" className="h-auto w-64" />
        </div>
        <div className="mt-0">
          <h1 className="mb-4 bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-5xl font-bold text-transparent">
            Cobra más fácil, más rápido y sin complicaciones
          </h1>
          <p className="mx-auto mb-8 max-w-2xl text-xl text-gray-600">
            Automatiza tus cobranzas y olvídate de las complicaciones. La
            solución perfecta para gimnasios, colegios y condominios.
          </p>
          <div className="flex justify-center gap-4">
            <Button
              asChild
              className="bg-primary px-8 py-6 text-lg text-white hover:bg-primary-dark"
              onClick={() => {}}
            >
              <a href="/tienda/registrar">Comenzar Ahora</a>
            </Button>
            {/* <Button
              variant="outline"
              className="border-primary text-primary hover:bg-primary/10 px-8 py-6 text-lg"
            >
              Solicitar Demo
            </Button> */}
          </div>
        </div>
      </div>
    </div>
  );
};
