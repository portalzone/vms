"""
SQLAlchemy models — mirrors the Laravel VMS database schema exactly.
"""
from sqlalchemy import Column, Integer, String, Float, Date, DateTime, Text, ForeignKey, Enum
from sqlalchemy.orm import relationship
from database import Base


class Vehicle(Base):
    __tablename__ = "vehicles"

    id               = Column(Integer, primary_key=True)
    manufacturer     = Column(String(255))
    model            = Column(String(255))
    year             = Column(Integer)
    plate_number     = Column(String(255), unique=True)
    ownership_type   = Column(Enum("organisation", "individual"))
    individual_type  = Column(Enum("staff", "visitor", "vehicle_owner"), nullable=True)
    owner_id         = Column(Integer, ForeignKey("users.id"), nullable=True)
    created_by       = Column(Integer, ForeignKey("users.id"), nullable=True)
    updated_by       = Column(Integer, ForeignKey("users.id"), nullable=True)
    created_at       = Column(DateTime)
    updated_at       = Column(DateTime)

    maintenances = relationship("Maintenance", back_populates="vehicle",
                                foreign_keys="Maintenance.vehicle_id")
    trips        = relationship("Trip", back_populates="vehicle")
    expenses     = relationship("Expense", back_populates="vehicle")
    incomes      = relationship("Income", back_populates="vehicle")
    drivers      = relationship("Driver", back_populates="vehicle")


class Driver(Base):
    __tablename__ = "drivers"

    id             = Column(Integer, primary_key=True)
    user_id        = Column(Integer, ForeignKey("users.id"))
    vehicle_id     = Column(Integer, ForeignKey("vehicles.id"), nullable=True)
    license_number = Column(String(255))
    phone_number   = Column(String(255))
    created_at     = Column(DateTime)
    updated_at     = Column(DateTime)

    vehicle = relationship("Vehicle", back_populates="drivers")
    trips   = relationship("Trip", back_populates="driver")
    incomes = relationship("Income", back_populates="driver")


class Maintenance(Base):
    __tablename__ = "maintenances"

    id          = Column(Integer, primary_key=True)
    vehicle_id  = Column(Integer, ForeignKey("vehicles.id"))
    description = Column(Text)
    status      = Column(String(50))
    cost        = Column(Float, nullable=True)
    date        = Column(Date)
    created_by  = Column(Integer, ForeignKey("users.id"), nullable=True)
    updated_by  = Column(Integer, ForeignKey("users.id"), nullable=True)
    created_at  = Column(DateTime)
    updated_at  = Column(DateTime)

    vehicle = relationship("Vehicle", back_populates="maintenances",
                           foreign_keys=[vehicle_id])
    expense = relationship("Expense", back_populates="maintenance", uselist=False)


class Expense(Base):
    __tablename__ = "expenses"

    id             = Column(Integer, primary_key=True)
    vehicle_id     = Column(Integer, ForeignKey("vehicles.id"))
    maintenance_id = Column(Integer, ForeignKey("maintenances.id"), nullable=True)
    amount         = Column(Float)
    description    = Column(Text)
    date           = Column(Date)
    created_by     = Column(Integer, ForeignKey("users.id"), nullable=True)
    updated_by     = Column(Integer, ForeignKey("users.id"), nullable=True)
    created_at     = Column(DateTime)
    updated_at     = Column(DateTime)

    vehicle     = relationship("Vehicle", back_populates="expenses")
    maintenance = relationship("Maintenance", back_populates="expense")


class Trip(Base):
    __tablename__ = "trips"

    id             = Column(Integer, primary_key=True)
    driver_id      = Column(Integer, ForeignKey("drivers.id"))
    vehicle_id     = Column(Integer, ForeignKey("vehicles.id"))
    start_location = Column(String(255))
    end_location   = Column(String(255))
    start_time     = Column(DateTime)
    end_time       = Column(DateTime, nullable=True)
    status         = Column(Enum("pending", "in_progress", "completed"))
    amount         = Column(Float, nullable=True)
    created_at     = Column(DateTime)
    updated_at     = Column(DateTime)

    vehicle = relationship("Vehicle", back_populates="trips")
    driver  = relationship("Driver", back_populates="trips")
    income  = relationship("Income", back_populates="trip", uselist=False)


class Income(Base):
    __tablename__ = "incomes"

    id          = Column(Integer, primary_key=True)
    vehicle_id  = Column(Integer, ForeignKey("vehicles.id"), nullable=True)
    driver_id   = Column(Integer, ForeignKey("drivers.id"), nullable=True)
    trip_id     = Column(Integer, ForeignKey("trips.id"), nullable=True)
    amount      = Column(Float)
    description = Column(Text, nullable=True)
    date        = Column(Date)
    created_at  = Column(DateTime)
    updated_at  = Column(DateTime)

    vehicle = relationship("Vehicle", back_populates="incomes")
    driver  = relationship("Driver", back_populates="incomes")
    trip    = relationship("Trip", back_populates="income")


class User(Base):
    __tablename__ = "users"

    id         = Column(Integer, primary_key=True)
    name       = Column(String(255))
    email      = Column(String(255), unique=True)
    created_at = Column(DateTime)
    updated_at = Column(DateTime)
