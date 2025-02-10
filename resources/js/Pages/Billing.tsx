import { ShoppingCart } from '@/components/ShoppingCart';
import { Button } from '@/components/ui/button';

const Billing = () => {
  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8 flex items-center justify-between">
        <h1 className="text-3xl font-bold">Facturación</h1>
        <Button
          variant="outline"
          onClick={() => {
            // navigate(-1)
          }}
        >
          Volver
        </Button>
      </div>
      <ShoppingCart />
    </div>
  );
};

export default Billing;
