import { Product } from "@/types/Product";
import Image from "next/image";

export default function ProductCard(props: { product: Product }) {
  const { product } = props;

  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex flex-col h-full">
      <div className="relative h-48">
        <Image
          src={product.image_url || "/placeholder-product.jpg"}
          alt={product.title}
          fill
          sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
          className="object-cover"
          priority
        />
      </div>
      <div className="p-4 flex flex-col flex-grow">
        <h2 className="font-semibold text-lg mb-2 line-clamp-2">
          {product.title}
        </h2>
        <div className="mt-auto pt-2">
          <p className="font-bold text-xl text-blue-600">${product.price}</p>
        </div>
      </div>
    </div>
  );
}
